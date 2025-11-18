<?php

namespace App\Traits\PaymentGateway;

use App\Constants\NotificationConst;
use App\Constants\PaymentGatewayConst;
use App\Http\Helpers\Api\Helpers;
use App\Models\Admin\BasicSettings;
use App\Models\TemporaryData;
use App\Providers\Admin\BasicSettingsProvider;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Traits\TransactionAgent;
use App\Traits\PayLink\TransactionTrait;

trait AuthorizeNetTrait
{
    use TransactionAgent, TransactionTrait;

    public function authorizeNetInit($output = null)
    {
        $basic_settings = BasicSettingsProvider::get();
        if (!$output) {
            $output = $this->output;
        }
        $credentials = $this->getAuthorizeNetCredentials($output);

        if ($output['type'] === PaymentGatewayConst::TYPEADDMONEY) {
            return $this->setupInitDataAddMoneyAuthorizeNet($output, $credentials, $basic_settings);
        } else {
            return $this->setupInitDataPayLinkAuthorizeNet($output, $credentials, $basic_settings);
        }
    }

    public function setupInitDataAddMoneyAuthorizeNet($output, $credentials, $basic_settings)
    {
        $reference = generateTransactionReference();
        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount, 2, '.', '') : 0;
        $currency = $output['currency']['currency_code'] ?? "USD";

        if (auth()->guard(get_auth_guard())->check()) {
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }

        if (userGuard()['guard'] === 'web') {
            $return_url = route('user.add.money.authorizenet.payment.success', $reference);
            $cancel_url = route('user.add.money.authorizenet.payment.cancel', $reference);
        } elseif (userGuard()['guard'] === 'agent') {
            $return_url = route('agent.add.money.authorizenet.payment.success', $reference);
            $cancel_url = route('agent.add.money.authorizenet.payment.cancel', $reference);
        }

        // Set the Authorize.Net environment
        if ($credentials->mode === 'sandbox') {
            \net\authorize\api\constants\ANetEnvironment::setSandbox();
        } else {
            \net\authorize\api\constants\ANetEnvironment::setProduction();
        }

        // Create the payment transaction
        $transactionRequestType = new \net\authorize\api\contract\v1\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($amount);

        // Set order information
        $order = new \net\authorize\api\contract\v1\OrderType();
        $order->setInvoiceNumber($reference);
        $order->setDescription("Add Money - ".$basic_settings->site_name);
        $transactionRequestType->setOrder($order);

        // Set customer information
        $customerData = new \net\authorize\api\contract\v1\CustomerDataType();
        $customerData->setEmail($user_email);
        $transactionRequestType->setCustomer($customerData);

        // Set hosted payment settings
        $setting = new \net\authorize\api\contract\v1\SettingType();
        $setting->setSettingName("hostedPaymentReturnOptions");
        $setting->setSettingValue(json_encode([
            'showReceipt' => true,
            'url' => $return_url,
            'urlText' => 'Continue',
            'cancelUrl' => $cancel_url,
            'cancelUrlText' => 'Cancel'
        ]));

        $settingList = [$setting];

        // Create request
        $request = new \net\authorize\api\contract\v1\GetHostedPaymentPageRequest();
        $request->setMerchantAuthentication($this->getAuthorizeNetMerchantAuth($credentials));
        $request->setTransactionRequest($transactionRequestType);
        $request->setHostedPaymentSettings($settingList);

        try {
            $controller = new \net\authorize\api\controller\GetHostedPaymentPageController($request);
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::get());

            if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                $this->authorizeNetJunkInsert($output, $reference);
                
                return redirect($response->getToken());
            } else {
                $errorMessages = $response->getMessages()->getMessage();
                throw new Exception(__("Authorize.Net Error: ") . $errorMessages[0]->getText());
            }
        } catch (Exception $e) {
            throw new Exception(__("Failed to initialize Authorize.Net payment: ").$e->getMessage());
        }
    }

    public function setupInitDataPayLinkAuthorizeNet($output, $credentials, $basic_settings)
    {
        $reference = generateTransactionReference();
        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount, 2, '.', '') : 0;
        $currency = $output['currency']['currency_code'] ?? "USD";

        $return_url = route('payment-link.authorizenet.payment.success', $reference);
        $cancel_url = route('payment-link.authorizenet.payment.cancel', $reference);

        // Set the Authorize.Net environment
        if ($credentials->mode === 'sandbox') {
            \net\authorize\api\constants\ANetEnvironment::setSandbox();
        } else {
            \net\authorize\api\constants\ANetEnvironment::setProduction();
        }

        // Create the payment transaction
        $transactionRequestType = new \net\authorize\api\contract\v1\TransactionRequestType();
        $transactionRequestType->setTransactionType("authCaptureTransaction");
        $transactionRequestType->setAmount($amount);

        // Set order information
        $order = new \net\authorize\api\contract\v1\OrderType();
        $order->setInvoiceNumber($reference);
        $order->setDescription("Payment Link - ".$basic_settings->site_name);
        $transactionRequestType->setOrder($order);

        // Set hosted payment settings
        $setting = new \net\authorize\api\contract\v1\SettingType();
        $setting->setSettingName("hostedPaymentReturnOptions");
        $setting->setSettingValue(json_encode([
            'showReceipt' => true,
            'url' => $return_url,
            'urlText' => 'Continue',
            'cancelUrl' => $cancel_url,
            'cancelUrlText' => 'Cancel'
        ]));

        $settingList = [$setting];

        // Create request
        $request = new \net\authorize\api\contract\v1\GetHostedPaymentPageRequest();
        $request->setMerchantAuthentication($this->getAuthorizeNetMerchantAuth($credentials));
        $request->setTransactionRequest($transactionRequestType);
        $request->setHostedPaymentSettings($settingList);

        try {
            $controller = new \net\authorize\api\controller\GetHostedPaymentPageController($request);
            $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::get());

            if ($response != null && $response->getMessages()->getResultCode() == "Ok") {
                $this->authorizeNetJunkInsertPayLink($output, $reference);
                
                return redirect($response->getToken());
            } else {
                $errorMessages = $response->getMessages()->getMessage();
                throw new Exception(__("Authorize.Net Error: ") . $errorMessages[0]->getText());
            }
        } catch (Exception $e) {
            throw new Exception(__("Failed to initialize Authorize.Net payment: ").$e->getMessage());
        }
    }

    public function getAuthorizeNetCredentials($output)
    {
        $gateway = $output['gateway'] ?? null;
        if (!$gateway) {
            throw new Exception(__("Payment gateway not available"));
        }

        $credentials_sample = [
            'api_login_id' => 'your_api_login_id',
            'transaction_key' => 'your_transaction_key',
            'mode' => 'sandbox'
        ];
        
        $credentials = $gateway->credentials ?? [];

        if (array_key_exists("api_login_id", $credentials) && array_key_exists("transaction_key", $credentials)) {
            return (object) $credentials;
        }

        throw new Exception(__("Invalid Authorize.Net credentials"));
    }

    public function getAuthorizeNetMerchantAuth($credentials)
    {
        $merchantAuthentication = new \net\authorize\api\contract\v1\MerchantAuthenticationType();
        $merchantAuthentication->setName($credentials->api_login_id);
        $merchantAuthentication->setTransactionKey($credentials->transaction_key);
        
        return $merchantAuthentication;
    }

    public function authorizeNetJunkInsert($output, $reference)
    {
        $data = [
            'gateway'      => $output['gateway']->id,
            'currency'     => $output['currency']->id,
            'amount'       => json_decode(json_encode($output['amount']), true),
            'response'     => ['reference' => $reference],
            'creator_table' => auth()->guard(get_auth_guard())->user()->getTable(),
            'creator_id'    => auth()->guard(get_auth_guard())->user()->id,
            'creator_guard' => get_auth_guard(),
        ];

        return TemporaryData::create([
            'user_id'    => auth()->guard(userGuard()['guard'])->user()->id,
            'type'       => PaymentGatewayConst::TYPEADDMONEY,
            'identifier' => $reference,
            'data'       => $data,
        ]);
    }

    public function authorizeNetJunkInsertPayLink($output, $reference)
    {
        $data = [
            'gateway'      => $output['gateway']->id,
            'currency'     => $output['currency']->id,
            'amount'       => json_decode(json_encode($output['amount']), true),
            'response'     => ['reference' => $reference],
        ];

        return TemporaryData::create([
            'type'       => PaymentGatewayConst::TYPEPAYLINK,
            'identifier' => $reference,
            'data'       => $data,
        ]);
    }
}
