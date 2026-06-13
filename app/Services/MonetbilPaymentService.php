<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MonetbilPaymentService
{
    public function placePayment(PaymentTransaction $transaction): PaymentTransaction
    {
        $serviceKey = $transaction->type === \App\Models\PaymentTransaction::TYPE_ENERGY_TOKEN
            ? config('services.monetbil.token.service_key')
            : config('services.monetbil.shop.service_key');

        $payload = [
            'service' => $serviceKey,
            'phonenumber' => $this->phoneForMonetbil($transaction->customer_phone),
            'amount' => (int) round((float) $transaction->total_amount),
            'currency' => $transaction->currency,
            'country' => $transaction->country,
            'item_ref' => $transaction->type,
            'payment_ref' => $transaction->reference,
            'notify_url' => route('payments.monetbil.callback'),
        ];

        if (config('services.monetbil.send_operator') && $transaction->operator_code) {
            $payload['operator'] = $transaction->operator_code;
        }

        if (config('services.monetbil.fake_mode')) {
            $response = $this->fakePlaceResponse($transaction);
        } else {
            $response = Http::withOptions([
                'verify' => (bool) config('services.monetbil.verify_ssl', true),
            ])
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($this->endpoint('placePayment'), $payload)
                ->json();
        }

        $transaction->fill([
            'request_payload' => $payload,
            'response_payload' => $response,
            'monetbil_payment_id' => $response['paymentId'] ?? $transaction->monetbil_payment_id,
            'monetbil_status' => $response['status'] ?? null,
            'monetbil_message' => $response['message'] ?? null,
        ]);

        if (($response['status'] ?? null) !== 'REQUEST_ACCEPTED') {
            $transaction->status = PaymentTransaction::STATUS_FAILED;
            $transaction->failed_at = now();
            $transaction->failure_reason = $this->readableMessage($response['message'] ?? 'Paiement Monetbil refuse.');
        }

        $transaction->save();

        return $transaction;
    }

    public function checkPayment(PaymentTransaction $transaction): array
    {
        if (! $transaction->monetbil_payment_id) {
            return [
                'message' => 'payment pending',
                'status' => 'pending',
            ];
        }

        if (config('services.monetbil.fake_mode')) {
            $response = $this->fakeCheckResponse($transaction);
        } else {
            $response = Http::withOptions([
                'verify' => (bool) config('services.monetbil.verify_ssl', true),
            ])
                ->acceptJson()
                ->asForm()
                ->timeout(30)
                ->post($this->endpoint('checkPayment'), [
                    'paymentId' => $transaction->monetbil_payment_id,
                ])
                ->json();
        }

        $transaction->fill([
            'last_check_payload' => $response,
            'monetbil_transaction_uuid' => $response['transaction']['transaction_UUID'] ?? $transaction->monetbil_transaction_uuid,
            'monetbil_status' => $response['transaction']['status'] ?? $response['status'] ?? $transaction->monetbil_status,
            'monetbil_message' => $response['transaction']['message'] ?? $response['message'] ?? $transaction->monetbil_message,
        ])->save();

        return $response;
    }

    public function transactionSucceeded(array $response): bool
    {
        return (string) data_get($response, 'transaction.status') === '1';
    }

    public function transactionFailed(array $response): bool
    {
        return in_array((string) data_get($response, 'transaction.status'), ['0', '-1', '-2'], true);
    }

    public function operatorFromPhone(?string $phone): ?array
    {
        $digits = $this->localCameroonDigits($phone);

        if (strlen($digits) !== 9) {
            return null;
        }

        foreach (config('services.monetbil.number_validation.prefixes', []) as $key => $prefixes) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($digits, $prefix)) {
                    return [
                        'key' => $key,
                        'label' => config("services.monetbil.number_validation.labels.{$key}", $key),
                        'code' => config("services.monetbil.operators.{$key}"),
                    ];
                }
            }
        }

        return null;
    }

    public function localCameroonDigits(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if (str_starts_with($digits, '237') && strlen($digits) > 9) {
            $digits = substr($digits, 3);
        }

        return substr($digits, 0, 9);
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.monetbil.base_url'), '/').'/'.$path;
    }

    private function readableMessage(?string $message): string
    {
        return match ($message) {
            'LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED' => 'Solde insuffisant, limite atteinte ou paiement non autorise.',
            default => $message ?: 'Paiement Monetbil refuse.',
        };
    }

    private function phoneForMonetbil(?string $phone): string
    {
        return '237'.$this->localCameroonDigits($phone);
    }

    private function fakePlaceResponse(PaymentTransaction $transaction): array
    {
        return [
            'status' => 'REQUEST_ACCEPTED',
            'message' => 'payment pending',
            'channel_name' => $transaction->operator_label,
            'channel' => $transaction->operator_code,
            'paymentId' => 'FAKE-'.Str::upper(Str::random(14)),
        ];
    }

    private function fakeCheckResponse(PaymentTransaction $transaction): array
    {
        $result = config('services.monetbil.fake_result', 'success');

        if ($result === 'pending') {
            return [
                'paymentId' => $transaction->monetbil_payment_id,
                'message' => 'payment pending',
            ];
        }

        return [
            'paymentId' => $transaction->monetbil_payment_id,
            'message' => 'payment finish',
            'transaction' => [
                'transaction_UUID' => 'FAKE-TX-'.Str::upper(Str::random(12)),
                'status' => $result === 'success' ? 1 : 0,
                'message' => $result === 'success' ? 'success' : 'failed',
                'msisdn' => $this->phoneForMonetbil($transaction->customer_phone),
                'amount' => (int) round((float) $transaction->total_amount),
                'fee' => (int) round((float) $transaction->operator_fee),
                'currency' => $transaction->currency,
                'payment_ref' => $transaction->reference,
            ],
        ];
    }
}
