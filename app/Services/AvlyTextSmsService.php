<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AvlyTextSmsService
{
    public function sendEnergyTokenSms(Sale $sale): void
    {
        $item = $sale->items()->where('service_name', 'Token Energie')->first();
        $payload = $item?->service_payload ?? [];

        if (! $item || ! ($payload['send_sms'] ?? false)) {
            return;
        }

        $phone = $payload['sms_phone'] ?? null;
        $token = $payload['token'] ?? null;

        if (! $phone || ! $token) {
            $this->markSms($item, [
                'sms_status' => 'failed',
                'sms_error' => 'Numero SMS ou token absent.',
            ]);

            return;
        }

        $apiKey = (string) config('services.avlytext.api_key', '');

        if ($apiKey === '') {
            $this->markSms($item, [
                'sms_status' => 'config_missing',
                'sms_error' => 'AVLYTEXT_API_KEY manquante.',
            ]);

            return;
        }

        $message = $this->tokenMessage($payload);

        try {
            $recipient = $this->internationalCameroonPhone($phone);
            $operator = $this->detectOperator($recipient);
            $sender = $this->senderForOperator($operator);

            Log::info('AvlyText SMS dispatch.', [
                'sale_id' => $sale->id,
                'operator' => $operator,
                'sender' => $sender,
                'recipient' => $recipient,
            ]);

            $response = Http::withOptions([
                'verify' => (bool) config('services.avlytext.verify_ssl', true),
            ])
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->post($this->endpoint('sms'), [
                    'sender' => $sender,
                    'recipient' => $recipient,
                    'text' => $message,
                ]);

            $data = $response->json() ?? [];

            if (! $response->successful()) {
                $this->markSms($item, [
                    'sms_status' => 'failed',
                    'sms_operator' => $operator,
                    'sms_error' => $data['message'] ?? $response->body(),
                    'sms_response' => $data,
                ]);

                return;
            }

            $this->markSms($item, [
                'sms_status' => 'sent',
                'sms_operator' => $operator,
                'sms_provider_id' => $data['id'] ?? null,
                'sms_cost' => $data['cost'] ?? null,
                'sms_parts' => $data['parts'] ?? null,
                'sms_response' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AvlyText SMS token failed.', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);

            $this->markSms($item, [
                'sms_status' => 'failed',
                'sms_error' => $e->getMessage(),
            ]);
        }
    }

    private function detectOperator(string $internationalPhone): string
    {
        $local = preg_replace('/^\+237/', '', $internationalPhone);
        $prefix = (int) substr($local, 0, 2);

        return match (true) {
            in_array($prefix, [67, 68], true) => 'MTN',
            in_array($prefix, [65, 69], true) => 'Orange',
            in_array($prefix, [66], true) => 'Nexttel',
            default => 'Unknown',
        };
    }

    private function senderForOperator(string $operator): string
    {
        $mtnSender = (string) config('services.avlytext.sender_mtn', '');

        if ($operator === 'MTN' && $mtnSender !== '') {
            return $mtnSender;
        }

        return (string) config('services.avlytext.sender', 'SmartCity');
    }

    private function tokenMessage(array $payload): string
    {
        $lines = [
            now()->format('d/m/Y/ H:i'),
            'Pdv/SC1',
            (string) ($payload['room_number'] ?? 'N/A'),
            $this->formatKwh($payload['kwh'] ?? 0).'KWH/'.$this->formatMoney($payload['token_amount'] ?? 0).' F',
            'Token: '.$this->formatToken((string) ($payload['token'] ?? '')),
        ];

        $smsFee = (float) ($payload['sms_fee'] ?? 0);
        if ($smsFee > 0) {
            $lines[] = 'SMS: '.$this->formatMoney($smsFee).' F';
        }

        $operatorFee = (float) ($payload['operator_fee'] ?? 0);
        $paymentMethod = $payload['payment_method'] ?? null;
        if ($operatorFee > 0 && $paymentMethod !== 'cash') {
            $lines[] = 'Frais Momo: '.$this->formatMoney($operatorFee).' F';
        }

        return implode("\n", $lines);
    }

    private function formatToken(string $token): string
    {
        return trim(implode(' ', str_split(preg_replace('/\s+/', '', $token), 4)));
    }

    private function formatKwh(mixed $value): string
    {
        $number = (float) $value;

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    private function formatMoney(mixed $value): string
    {
        $number = (float) $value;
        $decimals = floor($number) == $number ? 0 : 2;

        return number_format($number, $decimals, '.', ',');
    }

    private function internationalCameroonPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '237') && strlen($digits) > 9) {
            return '+'.$digits;
        }

        return '+237'.substr($digits, 0, 9);
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.avlytext.base_url'), '/').'/'.$path
            .'?api_key='.urlencode((string) config('services.avlytext.api_key'));
    }

    private function markSms(SaleItem $item, array $updates): void
    {
        $item->update([
            'service_payload' => array_merge($item->service_payload ?? [], $updates),
        ]);
    }
}
