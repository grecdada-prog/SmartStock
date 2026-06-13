<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    private int $created = 0;
    private array $skipped = [];
    private array $seenBarcodes = [];

    public function __construct(private User $manager)
    {
    }

    public function collection(Collection $rows): void
    {
        $category = $this->defaultCategory();

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $name = trim((string) $this->value($row, ['nom', 'name']));
            $barcode = preg_replace('/\s+/', '', (string) $this->value($row, ['code_barres', 'codebarres', 'barcode', 'code_barre']));
            $description = trim((string) $this->value($row, ['description', 'despciprtion']));
            $alertQuantity = $this->value($row, ['seuil_de_stock', 'seuil_stock', 'alert_quantity', 'seuil']);

            if ($name === '') {
                $this->skipped[] = "Ligne {$line}: nom manquant.";
                continue;
            }

            if ($barcode !== '' && ! preg_match('/^\d+$/', $barcode)) {
                $this->skipped[] = "Ligne {$line}: code-barres invalide pour {$name}.";
                continue;
            }

            if ($barcode !== '' && strlen($barcode) > 13) {
                $this->skipped[] = "Ligne {$line}: code-barres trop long pour {$name}.";
                continue;
            }

            if ($barcode !== '' && $this->barcodeAlreadyUsedByManager($barcode)) {
                $this->skipped[] = "Ligne {$line}: code-barres deja existant chez vous pour {$name}.";
                continue;
            }

            if ($barcode !== '') {
                $this->seenBarcodes[] = $barcode;
            }

            Product::create([
                'name' => $name,
                'sku' => $this->generateSku($name),
                'barcode' => $barcode !== '' ? $barcode : null,
                'description' => $description !== '' ? $description : null,
                'category_id' => $category->id,
                'purchase_price' => 0,
                'selling_price' => 0,
                'quantity' => 0,
                'alert_quantity' => max(0, (int) ($alertQuantity ?? 0)),
                'unit' => 'piece',
                'is_active' => true,
                'created_by' => $this->manager->id,
            ]);

            $this->created++;
        }
    }

    public function createdCount(): int
    {
        return $this->created;
    }

    public function skippedRows(): array
    {
        return $this->skipped;
    }

    private function value(Collection $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if ($row->has($key)) {
                return $row->get($key);
            }
        }

        return null;
    }

    private function defaultCategory(): Category
    {
        $superAdminIds = User::whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))->pluck('id');

        return Category::where(function ($query) use ($superAdminIds) {
                $query->where('created_by', $this->manager->id)
                    ->orWhereIn('created_by', $superAdminIds);
            })
            ->active()
            ->orderBy('created_by')
            ->orderBy('name')
            ->first()
            ?? Category::create([
                'name' => 'Divers et services',
                'description' => 'Categorie creee automatiquement pour les imports produits.',
                'is_active' => true,
                'created_by' => $this->manager->id,
            ]);
    }

    private function barcodeAlreadyUsedByManager(string $barcode): bool
    {
        return in_array($barcode, $this->seenBarcodes, true)
            || Product::withTrashed()
                ->where('created_by', $this->manager->id)
                ->where('barcode', $barcode)
                ->exists();
    }

    private function generateSku(string $productName): string
    {
        $prefix = Str::of($productName)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '')
            ->substr(0, 3)
            ->padRight(3, 'X')
            ->toString();

        do {
            $sku = $prefix.'-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }
}
