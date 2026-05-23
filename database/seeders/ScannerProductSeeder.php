<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ScannerProductSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::role('super_admin')->where('email', 'nanguefyllias@gmail.com')->first();

        if (! $superAdmin) {
            $this->command?->warn('Superadmin introuvable. Lancez RolePermissionSeeder avant ScannerProductSeeder.');

            return;
        }

        $owner = User::updateOrCreate(
            ['email' => 'bertholfyllias@gmail.com'],
            [
                'name' => 'Gerant SmartStore',
                'phone' => '690100200',
                'password' => Hash::make('Dorab237@berthol'),
                'is_active' => true,
                'email_verified_at' => now(),
                'created_by' => $superAdmin->id,
            ]
        );
        $owner->syncRoles(['manager']);

        $categories = collect([
            'Epicerie',
            'Boissons',
            'Hygiene',
            'Produits laitiers',
        ])->mapWithKeys(function (string $name) use ($owner) {
            $category = Category::updateOrCreate(
                ['name' => $name, 'created_by' => $owner->id],
                [
                    'description' => "Produits {$name}",
                    'is_active' => true,
                ]
            );

            return [$name => $category->id];
        });

        $products = [
            ['Huile raffinee Mayor 1L', 'SCAN-MAYOR-1L', '11012035024090', 'Epicerie', 900, 1200, 24, 5, 'bouteille'],
            ['Spaghetti Panzani 500g', 'SCAN-PANZANI-500G', '6970155170122', 'Epicerie', 450, 650, 36, 6, 'paquet'],
            ['Riz parfume Royal 5kg', 'SCAN-RIZ-ROYAL-5KG', '6972288593281', 'Epicerie', 4200, 5500, 18, 4, 'sac'],
            ['Savon Azur 400g', 'SCAN-SAVON-AZUR-400G', '8718182020144', 'Hygiene', 450, 650, 30, 6, 'piece'],
            ['Jus Planet Pomme 1L', 'SCAN-PLANET-POMME-1L', '6971249524005', 'Boissons', 750, 1100, 20, 5, 'brique'],
            ['Lait en poudre Nido 400g', 'SCAN-NIDO-400G', '6174000037053', 'Produits laitiers', 2600, 3300, 16, 4, 'boite'],
        ];

        foreach ($products as [$name, $sku, $barcode, $category, $purchasePrice, $sellingPrice, $quantity, $alertQuantity, $unit]) {
            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'barcode' => $barcode,
                    'description' => 'Produit initial compatible avec le scan code-barres.',
                    'category_id' => $categories[$category],
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'quantity' => $quantity,
                    'alert_quantity' => $alertQuantity,
                    'unit' => $unit,
                    'is_active' => true,
                    'created_by' => $owner->id,
                ]
            );

            StockMovement::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'reference' => 'INITIAL-' . $sku,
                ],
                [
                    'type' => 'in',
                    'quantity' => $quantity,
                    'quantity_before' => 0,
                    'quantity_after' => $quantity,
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'remaining_quantity' => $quantity,
                    'batch_code' => 'LOT-' . $sku,
                    'reason' => 'Stock initial avec code-barres',
                    'user_id' => $owner->id,
                ]
            );
        }

        $this->command?->info(count($products) . ' produits avec code-barres charges.');
    }
}
