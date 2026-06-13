<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$manager = App\Models\User::where('email', 'bertholfyllias@gmail.com')->firstOrFail();

$categories = collect([
    'Boissons' => 'Boissons et jus',
    'Epicerie' => 'Produits alimentaires courants',
    'Hygiene' => 'Produits hygiene et entretien',
    'Frais' => 'Produits frais et laitiers',
    'Maison' => 'Articles maison',
    'Bebe' => 'Produits pour bebe',
])->mapWithKeys(function ($description, $name) use ($manager) {
    $category = App\Models\Category::updateOrCreate(
        ['name' => $name, 'created_by' => $manager->id],
        ['description' => $description, 'is_active' => true]
    );

    return [$name => $category->id];
});

$items = [
    ['Eau Minerale Source 1.5L', 'Boissons', 230, 400, 95, 15, 'bouteille', 'normal'],
    ['Jus Orange 1L', 'Boissons', 650, 1000, 42, 10, 'brique', 'normal'],
    ['Soda Cola 50cl', 'Boissons', 300, 500, 70, 18, 'bouteille', 'normal'],
    ['Riz Parfume 5kg', 'Epicerie', 4200, 5500, 38, 8, 'sac', 'normal'],
    ['Spaghetti 500g', 'Epicerie', 350, 600, 120, 25, 'paquet', 'normal'],
    ['Huile Vegetale 1L', 'Epicerie', 950, 1400, 55, 12, 'bouteille', 'normal'],
    ['Savon Menager 400g', 'Hygiene', 280, 500, 64, 15, 'piece', 'normal'],
    ['Dentifrice Fraicheur', 'Hygiene', 650, 1000, 33, 8, 'tube', 'normal'],
    ['Lessive Poudre 1kg', 'Hygiene', 1100, 1600, 27, 7, 'paquet', 'normal'],
    ['Papier Toilette 6 rouleaux', 'Maison', 1200, 1800, 48, 10, 'pack', 'normal'],
    ['Couches Bebe M', 'Bebe', 4200, 6000, 21, 5, 'pack', 'normal'],
    ['Lingettes Bebe', 'Bebe', 900, 1400, 36, 8, 'paquet', 'normal'],
    ['Sucre Blanc 1kg', 'Epicerie', 650, 950, 4, 10, 'paquet', 'low'],
    ['Lait en Poudre 400g', 'Epicerie', 1800, 2500, 3, 8, 'boite', 'low'],
    ['Mayonnaise 500ml', 'Epicerie', 900, 1400, 5, 10, 'bocal', 'low'],
    ['The Noir 25 sachets', 'Epicerie', 550, 900, 2, 6, 'boite', 'low'],
    ['Mouchoirs Papier', 'Maison', 350, 600, 6, 12, 'paquet', 'low'],
    ['Savon Liquide 500ml', 'Hygiene', 950, 1500, 4, 9, 'flacon', 'low'],
    ['Eponge Vaisselle', 'Maison', 150, 300, 3, 15, 'piece', 'low'],
    ['Biscuit Chocolat', 'Epicerie', 250, 450, 7, 12, 'paquet', 'low'],
    ['Boisson Energisante', 'Boissons', 450, 800, 5, 10, 'canette', 'low'],
    ['Gel Douche 250ml', 'Hygiene', 700, 1200, 2, 6, 'flacon', 'low'],
    ['Farine Ble 1kg', 'Epicerie', 500, 800, 0, 10, 'paquet', 'out'],
    ['Cafe Moulu 250g', 'Epicerie', 1200, 1800, 0, 5, 'paquet', 'out'],
    ['Lait UHT 1L', 'Frais', 650, 1000, 0, 12, 'brique', 'out'],
    ['Fromage Portion', 'Frais', 900, 1400, 0, 6, 'boite', 'out'],
    ['Desinfectant Sol 1L', 'Hygiene', 800, 1300, 0, 8, 'bouteille', 'out'],
    ['Pile AA Pack 4', 'Maison', 900, 1500, 0, 5, 'pack', 'out'],
    ['Savon Bebe Doux', 'Bebe', 500, 900, 0, 6, 'piece', 'out'],
    ['Yaourt Nature 125g', 'Frais', 180, 300, 18, 8, 'pot', 'expiring', 2],
    ['Yaourt Fraise 125g', 'Frais', 190, 320, 15, 8, 'pot', 'expiring', 3],
    ['Jus Ananas Frais 50cl', 'Boissons', 450, 750, 12, 6, 'bouteille', 'expiring', 4],
    ['Pain de Mie', 'Frais', 650, 1000, 9, 6, 'paquet', 'expiring', 1],
    ['Saucisson 250g', 'Frais', 1400, 2200, 7, 4, 'piece', 'expiring', 5],
    ['Beurre 250g', 'Frais', 1200, 1800, 10, 5, 'plaquette', 'expiring', 6],
    ['Creme Dessert Vanille', 'Frais', 300, 500, 14, 8, 'pot', 'expiring', 7],
    ['Lait Fermente 500ml', 'Frais', 400, 650, 11, 6, 'bouteille', 'expiring', 2],
];

$created = 0;
$updated = 0;
$movements = 0;

Illuminate\Support\Facades\DB::transaction(function () use ($items, $categories, $manager, &$created, &$updated, &$movements) {
    foreach ($items as $index => $item) {
        [$name, $category, $purchase, $selling, $quantity, $alert, $unit, $status] = $item;
        $daysToExpire = $item[8] ?? null;
        $number = str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
        $sku = 'DORA-'.$number;
        $barcode = '23762026'.str_pad((string) ($index + 1), 5, '0', STR_PAD_LEFT);

        $existing = App\Models\Product::withTrashed()->where('sku', $sku)->first();
        $wasNew = ! $existing;

        if ($existing && $existing->trashed()) {
            $existing->restore();
        }

        $product = App\Models\Product::updateOrCreate(
            ['sku' => $sku],
            [
                'name' => $name,
                'barcode' => $barcode,
                'description' => 'Produit de demonstration pour SmartStore AI Assistant - statut: '.$status,
                'category_id' => $categories[$category],
                'purchase_price' => $purchase,
                'selling_price' => $selling,
                'quantity' => $quantity,
                'alert_quantity' => $alert,
                'unit' => $unit,
                'is_active' => true,
                'is_direct_restock_eligible' => in_array($status, ['low', 'out'], true),
                'created_by' => $manager->id,
            ]
        );

        $wasNew ? $created++ : $updated++;

        if ($quantity > 0) {
            App\Models\StockMovement::updateOrCreate(
                ['product_id' => $product->id, 'reference' => 'DORA-INITIAL-'.$sku],
                [
                    'type' => App\Models\StockMovement::TYPE_IN,
                    'quantity' => $quantity,
                    'quantity_before' => 0,
                    'quantity_after' => $quantity,
                    'purchase_price' => $purchase,
                    'selling_price' => $selling,
                    'remaining_quantity' => $quantity,
                    'batch_code' => 'DORA-LOT-'.$number,
                    'is_perishable' => $status === 'expiring',
                    'expiration_date' => $status === 'expiring' ? today()->addDays($daysToExpire) : null,
                    'reason' => $status === 'expiring'
                        ? 'Lot proche de peremption pour demonstration IA'
                        : 'Stock initial produits Dora',
                    'user_id' => $manager->id,
                ]
            );
            $movements++;
        }
    }
});

$summary = [
    'manager' => $manager->email,
    'created_products' => $created,
    'updated_products' => $updated,
    'stock_movements_upserted' => $movements,
    'total_dora_products' => App\Models\Product::where('created_by', $manager->id)->where('sku', 'like', 'DORA-%')->count(),
    'low_stock' => App\Models\Product::where('created_by', $manager->id)->where('sku', 'like', 'DORA-%')->whereColumn('quantity', '<=', 'alert_quantity')->where('quantity', '>', 0)->count(),
    'out_of_stock' => App\Models\Product::where('created_by', $manager->id)->where('sku', 'like', 'DORA-%')->where('quantity', '<=', 0)->count(),
    'expiring_batches' => App\Models\StockMovement::whereHas('product', fn ($query) => $query
        ->where('created_by', $manager->id)
        ->where('sku', 'like', 'DORA-%'))
        ->whereNotNull('expiration_date')
        ->where('remaining_quantity', '>', 0)
        ->count(),
];

echo json_encode($summary, JSON_PRETTY_PRINT).PHP_EOL;
