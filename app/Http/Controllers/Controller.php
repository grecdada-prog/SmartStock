<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class Controller
{
    use AuthorizesRequests;

    protected function normalizeContactInputs(Request $request, array $phoneFields = ['phone'], array $emailFields = ['email']): void
    {
        foreach ($emailFields as $field) {
            if ($request->filled($field)) {
                $request->merge([
                    $field => strtolower(trim((string) $request->input($field))),
                ]);
            }
        }

        foreach ($phoneFields as $field) {
            if ($request->has($field)) {
                $digits = preg_replace('/\D+/', '', (string) $request->input($field));

                $request->merge([
                    $field => $digits === '' ? null : $digits,
                ]);
            }
        }
    }

    protected function strictEmailRules(string $uniqueRule): array
    {
        return [
            'required',
            'string',
            'email:rfc,filter',
            'max:255',
            $uniqueRule,
            'regex:/^(?!.*\.\.)[A-Z0-9](?:[A-Z0-9._%+\-]{0,62}[A-Z0-9])?@(?:[A-Z0-9](?:[A-Z0-9\-]{0,61}[A-Z0-9])?\.)+[A-Z]{2,63}$/i',
        ];
    }

    protected function phoneRules(): array
    {
        return ['nullable', 'regex:/^[0-9]{9,15}$/'];
    }

    protected function contactValidationMessages(): array
    {
        return [
            'email.email' => 'Le format de l\'email est invalide.',
            'email.regex' => 'Le format de l\'email est invalide.',
            'phone.regex' => 'Le telephone doit contenir 9 a 15 chiffres.',
            'customer_phone.regex' => 'Le numero de telephone doit contenir 9 a 15 chiffres.',
        ];
    }
}

