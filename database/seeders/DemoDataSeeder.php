<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'nanguefyllias@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Password@123'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $manager = User::updateOrCreate(
            ['email' => 'manager@smartstock.test'],
            [
                'name' => 'Gerant Demo',
                'phone' => '690100200',
                'password' => Hash::make('Password@123'),
                'is_active' => true,
                'email_verified_at' => now(),
                'created_by' => $superAdmin->id,
            ]
        );
        $manager->syncRoles(['manager']);

        $seller = User::updateOrCreate(
            ['email' => 'seller@smartstock.test'],
            [
                'name' => 'Vendeur Demo',
                'phone' => '690100201',
                'password' => Hash::make('Password@123'),
                'is_active' => true,
                'email_verified_at' => now(),
                'created_by' => $manager->id,
            ]
        );
        $seller->syncRoles(['seller']);

        $inactiveSeller = User::updateOrCreate(
            ['email' => 'seller.inactif@smartstock.test'],
            [
                'name' => 'Vendeur Inactif',
                'phone' => '690100202',
                'password' => Hash::make('Password@123'),
                'is_active' => false,
                'email_verified_at' => now(),
                'created_by' => $manager->id,
            ]
        );
        $inactiveSeller->syncRoles(['seller']);

        $categories = collect([
            ['name' => 'Boissons', 'description' => 'Boissons et rafraichissements'],
            ['name' => 'Epicerie', 'description' => 'Produits alimentaires courants'],
            ['name' => 'Hygiene', 'description' => 'Articles de soin et entretien'],
        ])->mapWithKeys(function (array $data) use ($manager) {
            $category = Category::updateOrCreate(
                ['name' => $data['name'], 'created_by' => $manager->id],
                [
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );

            return [$data['name'] => $category];
        });

        $products = collect([
            [
                'name' => 'Eau Minerale 1.5L',
                'sku' => 'DEMO-EAU-15',
                'category' => 'Boissons',
                'purchase_price' => 250,
                'selling_price' => 400,
                'quantity' => 80,
                'alert_quantity' => 12,
                'unit' => 'bouteille',
            ],
            [
                'name' => 'Riz Parfume 5kg',
                'sku' => 'DEMO-RIZ-5KG',
                'category' => 'Epicerie',
                'purchase_price' => 4200,
                'selling_price' => 5500,
                'quantity' => 18,
                'alert_quantity' => 8,
                'unit' => 'sac',
            ],
            [
                'name' => 'Savon Menager',
                'sku' => 'DEMO-SAVON-01',
                'category' => 'Hygiene',
                'purchase_price' => 300,
                'selling_price' => 500,
                'quantity' => 5,
                'alert_quantity' => 10,
                'unit' => 'piece',
            ],
        ])->map(function (array $data) use ($categories, $manager) {
            return Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'description' => 'Produit de demonstration SmartStock',
                    'category_id' => $categories[$data['category']]->id,
                    'purchase_price' => $data['purchase_price'],
                    'selling_price' => $data['selling_price'],
                    'quantity' => $data['quantity'],
                    'alert_quantity' => $data['alert_quantity'],
                    'unit' => $data['unit'],
                    'is_active' => true,
                    'created_by' => $manager->id,
                ]
            );
        });

        $sale = Sale::updateOrCreate(
            ['invoice_number' => 'INV-DEMO-0001'],
            [
                'seller_id' => $seller->id,
                'subtotal' => 6300,
                'tax' => 0,
                'discount' => 0,
                'total' => 6300,
                'amount_received' => 6500,
                'change_given' => 200,
                'customer_name' => 'Client Demo',
                'customer_phone' => '690100300',
                'payment_method' => 'cash',
                'notes' => 'Vente de demonstration',
            ]
        );

        $products->take(2)->each(function (Product $product, int $index) use ($sale, $seller) {
            $quantity = $index === 0 ? 2 : 1;

            SaleItem::updateOrCreate(
                ['sale_id' => $sale->id, 'product_id' => $product->id],
                [
                    'quantity' => $quantity,
                    'unit_price' => $product->selling_price,
                    'subtotal' => $quantity * $product->selling_price,
                ]
            );

            StockMovement::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'reference' => "Vente #{$sale->invoice_number}",
                ],
                [
                    'type' => 'out',
                    'quantity' => $quantity,
                    'quantity_before' => $product->quantity + $quantity,
                    'quantity_after' => $product->quantity,
                    'selling_price' => $product->selling_price,
                    'reason' => 'Vente de demonstration via POS',
                    'user_id' => $seller->id,
                ]
            );
        });

        $products->each(function (Product $product) use ($manager) {
            StockMovement::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'reference' => 'DEMO-STOCK-' . $product->sku,
                ],
                [
                    'type' => 'in',
                    'quantity' => 20,
                    'quantity_before' => max(0, $product->quantity - 20),
                    'quantity_after' => $product->quantity,
                    'reason' => 'Stock initial de demonstration',
                    'user_id' => $manager->id,
                ]
            );
        });

        ActivityLog::updateOrCreate(
            ['action' => 'demo_data_seeded', 'model' => 'Seeder', 'model_id' => null],
            [
                'user_id' => $superAdmin->id,
                'description' => 'Donnees de demonstration initialisees',
                'properties' => ['source' => self::class],
                'ip_address' => '127.0.0.1',
            ]
        );

        ActivityLog::updateOrCreate(
            ['action' => 'sale_created', 'model' => 'Sale', 'model_id' => $sale->id],
            [
                'user_id' => $seller->id,
                'description' => "Vente de demonstration : #{$sale->invoice_number} - Total: " . number_format($sale->total, 0, ',', ' ') . ' FCFA',
                'properties' => [
                    'invoice_number' => $sale->invoice_number,
                    'seller_id' => $seller->id,
                    'total' => (float) $sale->total,
                    'items_count' => $sale->items()->count(),
                    'payment_method' => $sale->payment_method,
                    'source' => self::class,
                ],
                'ip_address' => '127.0.0.1',
            ]
        );

        $this->command->info('Donnees de demo creees: manager@smartstock.test / seller@smartstock.test / Password@123');
    }
}
