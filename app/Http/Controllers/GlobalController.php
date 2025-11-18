<?php

namespace App\Http\Controllers;

use App\Constants\GlobalConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\Response;
use App\Models\Admin\ExchangeRate;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserWallet;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Facades\Agent;

class GlobalController extends Controller
{
    /**
     * Funtion for get state under a country
     * @param Request $request
     * @return JsonResponse
     */
    public function getStates(Request $request): JsonResponse {
        $request->validate([
            'country_id' => 'required|integer',
        ]);
        $country_id = $request->country_id;
        // Get All States From Country
        $country_states = get_country_states($country_id);
        return response()->json($country_states,200);
    }
    
    /**
     * Get cities under a state
     * @param Request $request
     * @return JsonResponse
     */
    public function getCities(Request $request): JsonResponse {
        $request->validate([
            'state_id' => 'required|integer',
        ]);

        $state_id = $request->state_id;
        $state_cities = get_state_cities($state_id);

        return response()->json($state_cities,200);
        // return $state_id;
    }
    
    /**
     * Get all countries
     * @param Request $request
     * @return JsonResponse
     */
    public function getCountries(Request $request): JsonResponse {
        $countries = get_all_countries();
        return response()->json($countries,200);
    }
    
    /**
     * Get countries for user
     * @param Request $request
     * @return JsonResponse
     */
    public function getCountriesUser(Request $request): JsonResponse {
        $countries = freedom_countries(GlobalConst::USER);
        return response()->json($countries,200);
    }
    
    /**
     * Get countries for agent
     * @param Request $request
     * @return JsonResponse
     */
    public function getCountriesAgent(Request $request): JsonResponse {
        $countries = freedom_countries(GlobalConst::AGENT);
        return response()->json($countries,200);
    }
    
    /**
     * Get countries for merchant
     * @param Request $request
     * @return JsonResponse
     */
    public function getCountriesMerchant(Request $request): JsonResponse {
        $countries = freedom_countries(GlobalConst::MERCHANT);
        return response()->json($countries,200);
    }
    
    /**
     * Get all timezones
     * @param Request $request
     * @return JsonResponse
     */
    public function getTimezones(Request $request): JsonResponse {
        $timeZones = get_all_timezones();

        return response()->json($timeZones,200);
    }
    /**
     * Get user information
     * @param Request $request
     * @return JsonResponse
     */
    public function userInfo(Request $request): JsonResponse {
        $validator = Validator::make($request->all(),[
            'text'      => "required|string",
        ]);
        if($validator->fails()) {
            return Response::error($validator->errors(),null,400);
        }
        $validated = $validator->validate();
        $field_name = "email";
        // if(check_email($validated['text'])) {
        //     $field_name = "email";
        // }

        try{
            $user = User::where($field_name,$validated['text'])->first();
            if($user != null) {
                if(@$user->address->country === null ||  @$user->address->country != get_default_currency_name()) {
                    $error = ['error' => [__("User Country doesn't match with default currency country")]];
                    return Response::error($error, null, 500);
                }
            }
        }catch(Exception $e) {
            $error = ['error' => [$e->getMessage()]];
            return Response::error($error,null,500);
        }
        $success = ['success' => [__('Successfully executed')]];
        return Response::success($success,$user,200);
    }
    /**
     * Handle webhook response
     * @param Request $request
     * @return JsonResponse|void
     */
    public function webHookResponse(Request $request){
        $request->validate([
            'data.reference' => 'required|string',
            'data.status' => 'required|string',
        ]);
        
        $response_data = $request->all();
        $transaction = Transaction::where('callback_ref',$response_data['data']['reference'])->first();

        if (!$transaction) {
            logger("Transaction not found for callback_ref: " . $response_data['data']['reference']);
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $update_temp_data = json_decode(json_encode($transaction->details),true);
        $update_temp_data['callback_data']  = $response_data;


        if($response_data['data']['status'] === "SUCCESSFUL" && $transaction->request_amount > $transaction->creator_wallet->balance ){
            $transaction->update([
                'status'    => PaymentGatewayConst::STATUSFAILD,
                'details'   => $update_temp_data,
                'reject_reason'   => "Insufficient Balance In Your Wallet",
                'available_balance' => $transaction->creator_wallet->balance,
            ]);
            logger("Transaction Status: " . PaymentGatewayConst::STATUSFAILD." Reason: Insufficient Balance In Your Wallet");

        }elseif($response_data['data']['status'] === "SUCCESSFUL"){
            $reduce_balance = ($transaction->creator_wallet->balance - $transaction->request_amount);
            $transaction->update([
                'status'            => PaymentGatewayConst::STATUSSUCCESS,
                'details'           => $update_temp_data,
                'available_balance' => $reduce_balance,
            ]);

            $transaction->creator_wallet->update([
                'balance'   => $reduce_balance,
            ]);
            logger("Transaction Status: " . $response_data['data']['status']);
        }elseif($response_data['data']['status'] === "FAILED"){

            $transaction->update([
                'status'    => PaymentGatewayConst::STATUSFAILD,
                'details'   => $update_temp_data,
                'reject_reason'   => $response_data['data']['complete_message']??null,
                'available_balance' => $transaction->creator_wallet->balance,
            ]);
            logger("Transaction Status: " . $response_data['data']['status']." Reason: ".$response_data['data']['complete_message']??"");
        }


    }
    /**
     * Set cookie preferences
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function setCookie(Request $request){
        $request->validate([
            'type' => 'required|in:allow,decline',
        ]);
        
        $userAgent = $request->header('User-Agent');
        $cookie_status = $request->type;
        if($cookie_status == 'allow'){
            $response_message = __("Cookie Allowed Success");
            $expirationTime = 2147483647; //Maximum Unix timestamp.
        } else {
            $response_message = __("Cookie Declined");
            $expirationTime = Carbon::now()->addHours(24)->timestamp;// Set the expiration time to 24 hours from now.
        }
        $browser = Agent::browser();
        $platform = Agent::platform();
        $ipAddress = $request->ip();
        // Set the expiration time to a very distant future
        return response($response_message)->cookie('approval_status', $cookie_status,$expirationTime)
                                            ->cookie('user_agent', $userAgent,$expirationTime)
                                            ->cookie('ip_address', $ipAddress,$expirationTime)
                                            ->cookie('browser', $browser,$expirationTime)
                                            ->cookie('platform', $platform,$expirationTime);

    }
     /**
      * Get user wallet balance by currency
      * @param Request $request
      * @return mixed
      */
     public function userWalletBalance(Request $request){
        $request->validate([
            'id' => 'required|integer',
        ]);
        
        $user_wallets = UserWallet::where(['user_id' => auth()->user()->id, 'currency_id' => $request->id])->first();
        
        if (!$user_wallets) {
            return response()->json(['error' => 'Wallet not found'], 404);
        }
        
        return $user_wallets->balance;
    }
    /**
     * Get receiver wallet information
     * @param Request $request
     * @return mixed
     */
    public function receiverWallet(Request $request){
        $request->validate([
            'code' => 'required|string',
        ]);
        
        $receiver_currency = ExchangeRate::where(['currency_code' => $request->code])->first();
        
        if (!$receiver_currency) {
            return response()->json(['error' => 'Currency not found'], 404);
        }
        
        return $receiver_currency;
    }
    /**
     * Handle Reloadly webhook response
     * @param Request $request
     * @return JsonResponse|void
     */
    public function webhookInfo(Request $request){
        $request->validate([
            'data.customIdentifier' => 'required|string',
            'data.status' => 'required|string',
        ]);
        
        $response_data = $request->all();
        $custom_identifier = $response_data['data']['customIdentifier'];
        $transaction = Transaction::where('type',PaymentGatewayConst::MOBILETOPUP)->where('callback_ref',$custom_identifier)->first();
        
        if (!$transaction) {
            logger("Transaction not found for custom_identifier: " . $custom_identifier);
            return response()->json(['error' => 'Transaction not found'], 404);
        }
        
        if( $response_data ['data']['status'] =="SUCCESSFUL"){
            $transaction->update([
                'status' => true,
            ]);
        }elseif($response_data ['data']['status'] !="SUCCESSFUL" ){
            $afterCharge = (($transaction->creator_wallet->balance + $transaction->details->charges->payable) - $transaction->details->charges->agent_total_commission);
            $transaction->update([
                'status'            => PaymentGatewayConst::STATUSREJECTED,
                'available_balance' =>  $afterCharge,
            ]);
            //refund balance
            $transaction->creator_wallet->update([
                'balance'   => $afterCharge,
            ]);

        }
        logger("Mobile Top Up Success!", ['custom_identifier' => $custom_identifier, 'status' => $response_data ['data']['status']]);
    }


}
