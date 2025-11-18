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

trait MollieTrait
{
    use TransactionAgent, TransactionTrait;

    public function mollieInit($output = null)
    {
        $basic_settings = BasicSettingsProvider::get();
        if (!$output) {
            $output = $this->output;
        }
        $credentials = $this->getMollieCredentials($output);

        if ($output['type'] === PaymentGatewayConst::TYPEADDMONEY) {
            return $this->setupInitDataAddMoneyMollie($output, $credentials, $basic_settings);
        } else {
            return $this->setupInitDataPayLinkMollie($output, $credentials, $basic_settings);
        }
    }

    public function setupInitDataAddMoneyMollie($output, $credentials, $basic_settings)
    {
        $reference = generateTransactionReference();
        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount, 2, '.', '') : 0;
        $currency = $output['currency']['currency_code'] ?? "EUR";

        if (auth()->guard(get_auth_guard())->check()) {
            $user = auth()->guard(get_auth_guard())->user();
            $user_email = $user->email;
            $user_name = $user->firstname.' '.$user->lastname ?? '';
        }

        if (userGuard()['guard'] === 'web') {
            $return_url = route('user.add.money.mollie.payment.success', $reference);
            $cancel_url = route('user.add.money.mollie.payment.cancel', $reference);
        } elseif (userGuard()['guard'] === 'agent') {
            $return_url = route('agent.add.money.mollie.payment.success', $reference);
            $cancel_url = route('agent.add.money.mollie.payment.cancel', $reference);
        }

        // Initialize Mollie client
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setApiKey($credentials->api_key);

        try {
            $payment = $mollie->payments->create([
                "amount" => [
                    "currency" => $currency,
                    "value" => $amount
                ],
                "description" => "Add Money - ".$basic_settings->site_name,
                "redirectUrl" => $return_url,
                "cancelUrl" => $cancel_url,
                "metadata" => [
                    "reference" => $reference,
                    "user_email" => $user_email,
                ],
            ]);

            $this->mollieJunkInsert($output, $reference);

            return redirect($payment->getCheckoutUrl());
        } catch (Exception $e) {
            throw new Exception(__("Failed to initialize Mollie payment: ").$e->getMessage());
        }
    }

    public function setupInitDataPayLinkMollie($output, $credentials, $basic_settings)
    {
        $reference = generateTransactionReference();
        $amount = $output['amount']->total_amount ? number_format($output['amount']->total_amount, 2, '.', '') : 0;
        $currency = $output['currency']['currency_code'] ?? "EUR";

        $return_url = route('payment-link.mollie.payment.success', $reference);
        $cancel_url = route('payment-link.mollie.payment.cancel', $reference);

        // Initialize Mollie client
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setApiKey($credentials->api_key);

        try {
            $payment = $mollie->payments->create([
                "amount" => [
                    "currency" => $currency,
                    "value" => $amount
                ],
                "description" => "Payment Link - ".$basic_settings->site_name,
                "redirectUrl" => $return_url,
                "cancelUrl" => $cancel_url,
                "metadata" => [
                    "reference" => $reference,
                ],
            ]);

            $this->mollieJunkInsertPayLink($output, $reference);

            return redirect($payment->getCheckoutUrl());
        } catch (Exception $e) {
            throw new Exception(__("Failed to initialize Mollie payment: ").$e->getMessage());
        }
    }

    public function getMollieCredentials($output)
    {
        $gateway = $output['gateway'] ?? null;
        if (!$gateway) {
            throw new Exception(__("Payment gateway not available"));
        }

        $api_key_sample = ['api_key' => "test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"];
        $api_key = $gateway->credentials ?? [];

        if (array_key_exists("api_key", $api_key)) {
            return (object) $api_key;
        }

        throw new Exception(__("Invalid Mollie credentials"));
    }

    public function mollieJunkInsert($output, $reference)
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

    public function mollieJunkInsertPayLink($output, $reference)
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
