<?php

namespace App\Services;

use App\Models\SaleItem;

class EnergyTokenRecentSaleService
{
    public function find(string $meterAddress, string $roomNumber, int $hours = 48): ?SaleItem
    {
        $meterAddress = strtoupper(trim($meterAddress));
        $roomNumber = strtoupper(preg_replace('/\s+/', '', $roomNumber));

        return SaleItem::where('service_name', 'Token Energie')
            ->where('created_at', '>=', now()->subHours($hours))
            ->latest()
            ->get()
            ->first(function (SaleItem $item) use ($meterAddress, $roomNumber) {
                $payload = $item->service_payload ?? [];

                return strtoupper($payload['meter_address'] ?? '') === $meterAddress
                    && strtoupper($payload['room_number'] ?? '') === $roomNumber;
            });
    }

    public function messageFor(SaleItem $item, string $meterAddress, string $roomNumber): string
    {
        $payload = $item->service_payload ?? [];
        $amount = (float) ($payload['token_amount'] ?? $item->unit_price ?? 0);

        return sprintf(
            '%s de %s a payé un token de %s FCFA il y a moins de 48h. Voulez-vous vraiment lui vendre encore ?',
            strtoupper(preg_replace('/\s+/', '', $roomNumber)),
            strtoupper(trim($meterAddress)),
            number_format($amount, 0, ',', ' ')
        );
    }
}
