<?php

namespace App\Http\Controllers;

use App\Services\MistralApiService;
use Illuminate\Http\Request;

class CodeImprovementController extends Controller
{
    protected $mistralApiService;

    public function __construct(MistralApiService $mistralApiService)
    {
        $this->mistralApiService = $mistralApiService;
    }

    public function improve(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $prompt = $request->input('code');
        $result = $this->mistralApiService->improveCode($prompt);

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 500);
        }

        // Extraire la réponse de l'API
        $improvedCode = $result['choices'][0]['message']['content'] ?? 'Aucune suggestion disponible.';

        return response()->json([
            'original_code' => $prompt,
            'improved_code' => $improvedCode,
        ]);
    }
}

