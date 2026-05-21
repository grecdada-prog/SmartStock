<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MonetbilPaymentService
{
    public const OPERATOR_ORANGE_MONEY = 'CM_ORANGEMONEY';
    public const OPERATOR_MTN_MOMO = 'CM_MTNMOBILEMONEY';

    public function enabled(): bool
    {
        return (bool) config('services.monetbil.enabled', false)
            && (string) config('services.monetbil.service_key') !== '';
    }

    public function placePayment(array $payload): array
    {
        $serviceKey = (string) config('services.monetbil.service_key');

        if ($serviceKey === '') {
            throw new RuntimeException('MONETBIL_SERVICE_KEY n est pas configure.');
        }

        $body = array_filter([
            'service' => $serviceKey,
            'phonenumber' => Arr::get($payload, 'phone'),
            'amount' => Arr::get($payload, 'amount'),
            'operator' => Arr::get($payload, 'operator'),
            'currency' => Arr::get($payload, 'currency', config('services.monetbil.currency', 'XAF')),
            'country' => Arr::get($payload, 'country', config('services.monetbil.country', 'CM')),
            'item_ref' => Arr::get($payload, 'item_ref'),
            'payment_ref' => Arr::get($payload, 'payment_ref', $this->newPaymentReference()),
            'user' => Arr::get($payload, 'user'),
            'first_name' => Arr::get($payload, 'first_name'),
            'last_name' => Arr::get($payload, 'last_name'),
            'email' => Arr::get($payload, 'email'),
            'notify_url' => Arr::get($payload, 'notify_url'),
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout((int) config('services.monetbil.timeout', 30))
                ->retry(
                    (int) config('services.monetbil.retry_times', 1),
                    (int) config('services.monetbil.retry_sleep_ms', 300)
                )
                ->post($this->paymentEndpoint('placePayment'), $body);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Monetbil est momentanement indisponible.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Monetbil a refuse la demande de paiement.');
        }

        return $response->json() ?? [];
    }

    public function operatorForCameroonPhone(string $phone): ?string
    {
        $local = $this->localCameroonPhone($phone);

        if (! $local) {
            return null;
        }

        $prefix = substr($local, 0, 3);

        foreach (config('services.monetbil.cameroon_prefixes.mtn', []) as $range) {
            if ($this->prefixMatches($prefix, $range)) {
                return self::OPERATOR_MTN_MOMO;
            }
        }

        foreach (config('services.monetbil.cameroon_prefixes.orange', []) as $range) {
            if ($this->prefixMatches($prefix, $range)) {
                return self::OPERATOR_ORANGE_MONEY;
            }
        }

        return null;
    }

    public function paymentMethodForOperator(string $operator): string
    {
        return $operator === self::OPERATOR_ORANGE_MONEY ? 'card' : 'mobile_money';
    }

    public function operatorLabel(?string $operator): string
    {
        return match ($operator) {
            self::OPERATOR_ORANGE_MONEY => 'Orange Money',
            self::OPERATOR_MTN_MOMO => 'MTN Momo',
            default => 'Paiement mobile',
        };
    }

    public function normalizeNotification(array $payload): array
    {
        $status = (string) Arr::get($payload, 'status', Arr::get($payload, 'transaction_status', ''));
        $message = (string) Arr::get($payload, 'message', Arr::get($payload, 'reason', ''));
        $paymentRef = (string) Arr::get($payload, 'payment_ref', Arr::get($payload, 'paymentRef', ''));
        $paymentId = (string) Arr::get($payload, 'paymentId', Arr::get($payload, 'payment_id', ''));

        return [
            'payment_ref' => $paymentRef,
            'payment_id' => $paymentId,
            'successful' => $this->notificationStatusIsSuccessful($status),
            'failed' => $this->notificationStatusIsFailed($status),
            'failure_reason' => $this->friendlyFailureReason($status, $message),
            'raw_status' => $status,
            'raw' => $payload,
        ];
    }

    public function createPaymentLink(array $payload): array
    {
        $serviceKey = (string) config('services.monetbil.service_key');

        if ($serviceKey === '') {
            throw new RuntimeException('MONETBIL_SERVICE_KEY n est pas configure.');
        }

        $body = array_filter([
            'amount' => Arr::get($payload, 'amount'),
            'phone' => Arr::get($payload, 'phone'),
            'phone_lock' => Arr::get($payload, 'phone_lock', true),
            'locale' => Arr::get($payload, 'locale', 'fr'),
            'operator' => Arr::get($payload, 'operator'),
            'country' => Arr::get($payload, 'country', config('services.monetbil.country', 'CM')),
            'currency' => Arr::get($payload, 'currency', config('services.monetbil.currency', 'XAF')),
            'item_ref' => Arr::get($payload, 'item_ref'),
            'payment_ref' => Arr::get($payload, 'payment_ref', $this->newPaymentReference()),
            'user' => Arr::get($payload, 'user'),
            'first_name' => Arr::get($payload, 'first_name'),
            'last_name' => Arr::get($payload, 'last_name'),
            'email' => Arr::get($payload, 'email'),
            'return_url' => Arr::get($payload, 'return_url'),
            'notify_url' => Arr::get($payload, 'notify_url'),
            'logo' => Arr::get($payload, 'logo'),
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->timeout((int) config('services.monetbil.timeout', 15))
                ->retry(
                    (int) config('services.monetbil.retry_times', 2),
                    (int) config('services.monetbil.retry_sleep_ms', 300)
                )
                ->post($this->widgetEndpoint($serviceKey), $body);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Monetbil est momentanement indisponible.', previous: $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Monetbil a refuse la creation du paiement.');
        }

        $data = $response->json();

        if (! data_get($data, 'success') || ! data_get($data, 'payment_url')) {
            throw new RuntimeException('Reponse Monetbil invalide.');
        }

        return [
            'success' => true,
            'payment_url' => data_get($data, 'payment_url'),
            'payment_ref' => $body['payment_ref'],
            'raw' => $data,
        ];
    }

    public function notificationBelongsToService(array $payload): bool
    {
        $serviceKey = (string) config('services.monetbil.service_key');

        return $serviceKey !== '' && hash_equals($serviceKey, (string) Arr::get($payload, 'service', ''));
    }

    public function isSuccessfulNotification(array $payload): bool
    {
        $notification = $this->normalizeNotification($payload);

        return $this->notificationBelongsToService($payload)
            && $notification['successful']
            && (float) Arr::get($payload, 'amount', 0) > 0;
    }

    public function newPaymentReference(): string
    {
        return 'MB-'.now()->format('YmdHis').'-'.Str::upper(Str::random(8));
    }

    private function widgetEndpoint(string $serviceKey): string
    {
        $baseUrl = rtrim((string) config('services.monetbil.base_url', 'https://api.monetbil.com'), '/');
        $version = trim((string) config('services.monetbil.version', 'v2.1'), '/');

        return "{$baseUrl}/widget/{$version}/{$serviceKey}";
    }

    private function paymentEndpoint(string $action): string
    {
        $baseUrl = rtrim((string) config('services.monetbil.base_url', 'https://api.monetbil.com'), '/');

        return "{$baseUrl}/payment/v1/{$action}";
    }

    private function localCameroonPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) === 12 && str_starts_with($digits, '237')) {
            $digits = substr($digits, 3);
        }

        return strlen($digits) === 9 && str_starts_with($digits, '6') ? $digits : null;
    }

    private function prefixMatches(string $prefix, string $range): bool
    {
        if (str_contains($range, '-')) {
            [$start, $end] = array_map('intval', explode('-', $range, 2));
            $value = (int) $prefix;

            return $value >= $start && $value <= $end;
        }

        return $prefix === $range;
    }

    private function notificationStatusIsSuccessful(string $status): bool
    {
        return in_array(strtolower($status), ['success', 'successful', 'paid', '1'], true);
    }

    private function notificationStatusIsFailed(string $status): bool
    {
        return in_array(strtolower($status), ['failed', 'failure', 'cancelled', 'canceled', 'error', '0'], true);
    }

    private function friendlyFailureReason(string $status, string $message): string
    {
        $text = strtolower($status.' '.$message);

        if (str_contains($text, 'insufficient') || str_contains($text, 'solde') || str_contains($text, 'fund')) {
            return 'Fonds insuffisants sur le compte mobile.';
        }

        return $message !== '' ? $message : 'Paiement mobile echoue.';
    }
}
