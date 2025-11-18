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

trait SquareTrait
{
    use TransactionAgent, TransactionTrait;

    public function squareInit($output = null)
    {
        $basic_settings = BasicSettingsProvider::get();
        if (!$output) {
            $output = $this->output;
        }
        $credentials = $this->getSquareCredentials($output);

        if ($output['type'] === PaymentGatewayConst::TYPEADDMONEY) {
            return $this->setupInitDataAddMoneySquare($output, $credentials, $basic_settings);
        } else {
            return $this->setupInitDataPayLinkSquare($output, $credentials, $basic_settings);
        }
    }

    public function setupInitDataAddMoneySquare($output, $credentials, $basic_settings)
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
            $return_url = route('user.add.money.square.payment.success', $reference);
            $cancel_url = route('user.add.money.square.payment.cancel', $reference);
        } elseif (userGuard()['guard'] === 'agent') {
            $return_url = route('agent.add.money.square.payment.success', $reference);
            $cancel_url = route('agent.add.money.square.payment.cancel', $reference);
        }

        // Initialize Square client
        $client = new \Square\SquareClient([
            'accessToken' => $credentials->access_token,
            'environment' => $credentials->mode === 'sandbox' ? \Square\Environment::SANDBOX : \Square\Environment::PRODUCTION,
        ]);

        try {
            $checkout_api = $client->getCheckoutApi();
            
            $money = new \Square\Models\Money();
            $money->setAmount($amount * 100); // Square uses smallest currency unit (cents)
            $money->setCurrency($currency);

            $checkout = new \Square\Models\CreatePaymentLinkRequest(Str::uuid());
            $checkout->setQuickPay($checkout->getQuickPay() ?? new \Square\Models\QuickPay(
                "Add Money - ".$basic_settings->site_name,
                $money,
                $credentials->location_id
            ));

            $api_response = $checkout_api->createPaymentLink($checkout);

            if ($api_response->isSuccess()) {
                $result = $api_response->getResult();
                $payment_link = $result->getPaymentLink();
                
                $this->squareJunkInsert($output, $reference, $payment_link->getId());

                return redirect($payment_link->getUrl());
            } else {
                $errors = $api_response->getErrors();
                throw new Exception(__("Square payment initialization failed"));
            }
        } catch (Exception $e) {
            throw new Exception(__("Failed to initialize Square payment: ").$e->getMessage());
        }
    }

    public function setupInitDataPayLinkSquare($output, $credentials, $basic_settings)
    {
        $reference = generateTransactionReference();
        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount, 2, '.', '') : 0;
        $currency = $output['currency']['currency_code'] ?? "USD";

        $return_url = route('payment-link.square.payment.success', $reference);
        $cancel_url = route('payment-link.square.payment.cancel', $reference);

        // Initialize Square client
        $client = new \Square\SquareClient([
            'accessToken' => $credentials->access_token,
            'environment' => $credentials->mode === 'sandbox' ? \Square\Environment::SANDBOX : \Square\Environment::PRODUCTION,
        ]);

        try {
            $checkout_api = $client->getCheckoutApi();
            
            $money = new \Square\Models\Money();
            $money->setAmount($amount * 100);
            $money->setCurrency($currency);

            $checkout = new \Square\Models\CreatePaymentLinkRequest(Str::uuid());
            $checkout->setQuickPay($checkout->getQuickPay() ?? new \Square\Models\QuickPay(
                "Payment Link - ".$basic_settings->site_name,
                $money,
                $credentials->location_id
            ));

            $api_response = $checkout_api->createPaymentLink($checkout);

            if ($api_response->isSuccess()) {
                $result = $api_response->getResult();
                $payment_link = $result->getPaymentLink();
                
                $this->squareJunkInsertPayLink($output, $reference, $payment_link->getId());

                return redirect($payment_link->getUrl());
            } else {
                throw new Exception(__("Square payment initialization failed"));
            }
        } catch (Exception $e) {
            throw new Exception(__("Failed to initialize Square payment: ").$e->getMessage());
        }
    }

    public function getSquareCredentials($output)
    {
        $gateway = $output['gateway'] ?? null;
        if (!$gateway) {
            throw new Exception(__("Payment gateway not available"));
        }

        $credentials_sample = [
            'access_token' => 'sandbox-sq0idb-XXXXXXXXXXXXXXXXXXXXXXXXXXXX',
            'location_id' => 'LXXXXXXXXXXXXXXXXXXX',
            'mode' => 'sandbox'
        ];
        
        $credentials = $gateway->credentials ?? [];

        if (array_key_exists("access_token", $credentials) && array_key_exists("location_id", $credentials)) {
            return (object) $credentials;
        }

        throw new Exception(__("Invalid Square credentials"));
    }

    public function squareJunkInsert($output, $reference, $payment_link_id)
    {
        $data = [
            'gateway'      => $output['gateway']->id,
            'currency'     => $output['currency']->id,
            'amount'       => json_decode(json_encode($output['amount']), true),
            'response'     => [
                'reference' => $reference,
                'payment_link_id' => $payment_link_id
            ],
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

    public function squareJunkInsertPayLink($output, $reference, $payment_link_id)
    {
        $data = [
            'gateway'      => $output['gateway']->id,
            'currency'     => $output['currency']->id,
            'amount'       => json_decode(json_encode($output['amount']), true),
            'response'     => [
                'reference' => $reference,
                'payment_link_id' => $payment_link_id
            ],
        ];

        return TemporaryData::create([
            'type'       => PaymentGatewayConst::TYPEPAYLINK,
            'identifier' => $reference,
            'data'       => $data,
        ]);
    }
}
