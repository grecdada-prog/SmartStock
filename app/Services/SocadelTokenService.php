<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SocadelTokenService
{
    public function generateReference(): string
    {
        return 'TOKEN-'.date('Ymd').'-'.Str::upper(Str::random(16));
    }

    public function sell(string $project, string $roomNumber, float $amount, string $transactionReference): array
    {
        if ((bool) config('services.socadel_token.fake_mode', false)) {
            return $this->fakeResponse($project, $roomNumber, $amount, $transactionReference);
        }

        $payload = [
            'project' => $project,
            'room' => $roomNumber,
            'amount' => (string) (int) round($amount),
            'transaction' => $transactionReference,
        ];

        try {
            $response = Http::withOptions([
                'verify' => (bool) config('services.socadel_token.verify_ssl', true),
            ])
                ->withHeaders([
                    'Authorization' => 'Bearer '.((string) config('services.socadel_token.api_key', '')),
                ])
                ->acceptJson()
                ->asJson()
                ->timeout(30)
                ->connectTimeout(10)
                ->post($this->endpoint(), $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $msg = $e->getMessage();

            if (str_contains($msg, 'cURL error 28') || str_contains($msg, 'Timeout')) {
                throw new \RuntimeException('Le serveur SOCADEL ne repond pas (delai depasse). Reessayez dans quelques instants.');
            }

            if (str_contains($msg, 'cURL error 6') || str_contains($msg, 'Could not resolve')) {
                throw new \RuntimeException('Impossible de joindre le serveur SOCADEL. Verifiez la connexion reseau du serveur.');
            }

            throw new \RuntimeException('Erreur de connexion a SOCADEL : impossible de contacter le serveur.');
        }

        $data = $response->json() ?? [];

        if (! $response->successful() || $this->apiRejected($data)) {
            throw new \RuntimeException($this->errorMessage($data, $response->body()));
        }

        $token = $this->extractToken($data);

        if (! $token) {
            throw new \RuntimeException('Token absent dans la reponse SOCADEL.');
        }

        return [
            'status' => (string) ($data['status'] ?? 'success'),
            'token' => $token,
            'provider' => 'socadel',
            'request_payload' => $payload,
            'response_payload' => $data,
        ];
    }

    private function endpoint(): string
    {
        return rtrim((string) config('services.socadel_token.base_url'), '/')
            .'/'.ltrim((string) config('services.socadel_token.endpoint'), '/');
    }

    private function extractToken(array $data): ?string
    {
        $token = data_get($data, 'token')
            ?? data_get($data, 'data.token')
            ?? data_get($data, 'result.token')
            ?? data_get($data, 'meter.token')
            ?? data_get($data, 'data.meter.token')
            ?? data_get($data, 'code')
            ?? data_get($data, 'data.code');

        return $token ? (string) $token : null;
    }

    private function errorMessage(array $data, string $fallback): string
    {
        $error = data_get($data, 'error');
        $message = data_get($data, 'message');

        return 'Token non genere : '.(string) (
            $error
            ?? $message
            ?? data_get($data, 'errors.0')
            ?? $fallback
            ?: 'Vente du token refusee par SOCADEL.'
        );
    }

    private function apiRejected(array $data): bool
    {
        return array_key_exists('status', $data) && in_array($data['status'], [false, 'false', 0, '0'], true);
    }

    private function fakeResponse(string $project, string $roomNumber, float $amount, string $transactionReference): array
    {
        $payload = [
            'project' => $project,
            'room' => $roomNumber,
            'amount' => (string) (int) round($amount),
            'transaction' => $transactionReference,
        ];

        return [
            'status' => 'simulated',
            'token' => collect(range(1, 5))
                ->map(fn () => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT))
                ->implode(' '),
            'provider' => 'simulated',
            'request_payload' => $payload,
            'response_payload' => [
                'id' => 'SIM-'.Str::upper(Str::random(12)),
            ],
        ];
    }
}
