<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MistralApiService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.mistral.api_key');
        $this->baseUrl = 'https://api.mistral.ai/v1/';
    }

    /**
     * Envoyer une requête à l'API Mistral pour améliorer du code.
     *
     * @param string $prompt Le code ou la description à améliorer.
     * @param string $model Le modèle à utiliser (ex: "mistral-tiny").
     * @return array
     */
    public function improveCode(string $prompt, string $model = 'mistral-tiny'): array
    {
        $url = $this->baseUrl . "models/{$model}/chat/completions";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Améliore ce code PHP ou donne-moi des suggestions pour l'optimiser. Voici le code :\n\n" . $prompt,
                ],
            ],
            'temperature' => 0.3, // Pour des réponses plus précises
            'max_tokens' => 1000, // Ajuste selon la taille de ton code
        ]);

        if ($response->successful()) {
            return $response->json();
        } else {
            Log::error('Erreur API Mistral : ' . $response->body());
            return ['error' => 'Erreur lors de l\'appel à l\'API Mistral.'];
        }
    }
}
