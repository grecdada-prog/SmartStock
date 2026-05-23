<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class CameroonSupermarketProductSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::role('manager')->orderBy('id')->first();

        if (!$manager) {
            $this->command?->warn('Aucun gerant trouve. Creez un gerant avant de charger les produits.');

            return;
        }

        $categories = collect([
            'Boissons',
            'Epicerie',
            'Produits laitiers',
            'Boulangerie',
            'Conserves',
            'Hygiene',
            'Entretien',
            'Bebe',
            'Surgeles',
            'Cosmetiques',
        ])->mapWithKeys(function (string $name) use ($manager) {
            $category = Category::updateOrCreate(
                ['name' => $name, 'created_by' => $manager->id],
                [
                    'description' => "Rayon {$name}",
                    'is_active' => true,
                ]
            );

            return [$name => $category->id];
        });

        $products = [
            ['Boissons', 'Eau Supermont 1.5L', 'CM-SUP-001', 250, 350, 72, 12, 'bouteille', '11012035024090'],
            ['Boissons', 'Eau Tangui 1.5L', 'CM-SUP-002', 250, 350, 80, 12, 'bouteille', '6970155170122'],
            ['Boissons', 'Top Grenadine 50cl', 'CM-SUP-003', 300, 500, 48, 10, 'bouteille', '6972288593281'],
            ['Boissons', 'Top Orange 50cl', 'CM-SUP-004', 300, 500, 45, 10, 'bouteille', '8718182020144'],
            ['Boissons', 'Djino Cocktail 50cl', 'CM-SUP-005', 350, 600, 36, 8, 'bouteille', '6971249524005'],
            ['Boissons', 'Jus Planet Pomme 1L', 'CM-SUP-006', 750, 1100, 24, 6, 'brique', '6174000037053'],
            ['Boissons', 'Malta Guinness 33cl', 'CM-SUP-007', 450, 650, 40, 8, 'bouteille'],
            ['Boissons', 'Cafe soluble Nescafe 50g', 'CM-SUP-008', 950, 1300, 18, 5, 'pot'],
            ['Epicerie', 'Riz parfume 5kg', 'CM-SUP-009', 4200, 5200, 30, 6, 'sac'],
            ['Epicerie', 'Riz casse 25kg', 'CM-SUP-010', 13500, 16500, 12, 3, 'sac'],
            ['Epicerie', 'Spaghetti Panzani 500g', 'CM-SUP-011', 450, 650, 60, 12, 'paquet'],
            ['Epicerie', 'Macaroni 500g', 'CM-SUP-012', 400, 600, 55, 12, 'paquet'],
            ['Epicerie', 'Huile Mayor 1L', 'CM-SUP-013', 1150, 1450, 35, 8, 'bouteille'],
            ['Epicerie', 'Huile Dinor 1L', 'CM-SUP-014', 1200, 1500, 30, 8, 'bouteille'],
            ['Epicerie', 'Sucre blanc SOSUCAM 1kg', 'CM-SUP-015', 700, 900, 45, 10, 'paquet'],
            ['Epicerie', 'Farine de ble 1kg', 'CM-SUP-016', 550, 750, 40, 8, 'paquet'],
            ['Epicerie', 'Sel fin 1kg', 'CM-SUP-017', 250, 400, 50, 10, 'paquet'],
            ['Epicerie', 'Cube Maggi 60 pieces', 'CM-SUP-018', 800, 1100, 36, 8, 'boite'],
            ['Epicerie', 'Arome Maggi 200ml', 'CM-SUP-019', 600, 850, 28, 6, 'bouteille'],
            ['Epicerie', 'Mayonnaise 500ml', 'CM-SUP-020', 1100, 1500, 22, 5, 'pot'],
            ['Produits laitiers', 'Lait en poudre Nido 400g', 'CM-SUP-021', 2600, 3300, 18, 5, 'boite'],
            ['Produits laitiers', 'Lait Gloria 170g', 'CM-SUP-022', 350, 500, 72, 12, 'boite'],
            ['Produits laitiers', 'Lait concentre sucre 397g', 'CM-SUP-023', 700, 950, 36, 8, 'boite'],
            ['Produits laitiers', 'Yaourt nature Dolait 125g', 'CM-SUP-024', 200, 300, 60, 12, 'pot'],
            ['Produits laitiers', 'Yaourt aromatise 125g', 'CM-SUP-025', 220, 350, 60, 12, 'pot'],
            ['Produits laitiers', 'Beurre margarine 250g', 'CM-SUP-026', 850, 1200, 24, 6, 'pot'],
            ['Boulangerie', 'Pain de mie tranche', 'CM-SUP-027', 650, 900, 20, 5, 'paquet'],
            ['Boulangerie', 'Biscuit Parle-G', 'CM-SUP-028', 150, 250, 80, 15, 'paquet'],
            ['Boulangerie', 'Biscuit Glucose 150g', 'CM-SUP-029', 250, 400, 60, 12, 'paquet'],
            ['Boulangerie', 'Madeleine paquet 10 pieces', 'CM-SUP-030', 700, 1000, 26, 6, 'paquet'],
            ['Conserves', 'Sardines tomate 125g', 'CM-SUP-031', 500, 700, 48, 10, 'boite'],
            ['Conserves', 'Sardines huile 125g', 'CM-SUP-032', 550, 750, 45, 10, 'boite'],
            ['Conserves', 'Tomate concentree 400g', 'CM-SUP-033', 650, 900, 36, 8, 'boite'],
            ['Conserves', 'Mais doux 340g', 'CM-SUP-034', 750, 1100, 20, 5, 'boite'],
            ['Conserves', 'Haricots blancs 400g', 'CM-SUP-035', 700, 1000, 22, 5, 'boite'],
            ['Conserves', 'Thon en conserve 160g', 'CM-SUP-036', 950, 1300, 20, 5, 'boite'],
            ['Hygiene', 'Savon Azur 400g', 'CM-SUP-037', 450, 650, 45, 10, 'piece'],
            ['Hygiene', 'Savon de toilette 125g', 'CM-SUP-038', 350, 500, 55, 12, 'piece'],
            ['Hygiene', 'Dentifrice Signal 100ml', 'CM-SUP-039', 900, 1200, 30, 6, 'tube'],
            ['Hygiene', 'Brosse a dents medium', 'CM-SUP-040', 450, 700, 35, 8, 'piece'],
            ['Hygiene', 'Papier hygienique 4 rouleaux', 'CM-SUP-041', 850, 1200, 28, 6, 'paquet'],
            ['Hygiene', 'Gel douche 500ml', 'CM-SUP-042', 1500, 2200, 18, 5, 'flacon'],
            ['Entretien', 'Detergent Omo 500g', 'CM-SUP-043', 750, 1000, 40, 8, 'paquet'],
            ['Entretien', 'Lessive en poudre 1kg', 'CM-SUP-044', 1200, 1600, 30, 6, 'paquet'],
            ['Entretien', 'Eau de javel 1L', 'CM-SUP-045', 500, 750, 32, 6, 'bouteille'],
            ['Entretien', 'Liquide vaisselle 500ml', 'CM-SUP-046', 700, 1000, 30, 6, 'bouteille'],
            ['Entretien', 'Insecticide aerosol 300ml', 'CM-SUP-047', 1400, 2000, 18, 5, 'bombe'],
            ['Entretien', 'Serpilliere microfibre', 'CM-SUP-048', 700, 1000, 20, 5, 'piece'],
            ['Bebe', 'Couches bebe taille M 20 pieces', 'CM-SUP-049', 2800, 3800, 18, 5, 'paquet'],
            ['Bebe', 'Lingettes bebe 80 pieces', 'CM-SUP-050', 900, 1300, 24, 6, 'paquet'],
            ['Bebe', 'Lait infantile 400g', 'CM-SUP-051', 3800, 4800, 12, 3, 'boite'],
            ['Bebe', 'Savon bebe 100g', 'CM-SUP-052', 450, 700, 30, 6, 'piece'],
            ['Surgeles', 'Poulet entier congele 1kg', 'CM-SUP-053', 2300, 3000, 20, 5, 'kg'],
            ['Surgeles', 'Poisson maquereau congele 1kg', 'CM-SUP-054', 1800, 2400, 22, 5, 'kg'],
            ['Surgeles', 'Frites surgelees 1kg', 'CM-SUP-055', 1600, 2300, 18, 5, 'paquet'],
            ['Surgeles', 'Crevettes surgelees 500g', 'CM-SUP-056', 3000, 4200, 12, 3, 'paquet'],
            ['Cosmetiques', 'Creme hydratante 250ml', 'CM-SUP-057', 1200, 1800, 18, 5, 'pot'],
            ['Cosmetiques', 'Deodorant roll-on 50ml', 'CM-SUP-058', 900, 1300, 24, 6, 'piece'],
            ['Cosmetiques', 'Shampoing 400ml', 'CM-SUP-059', 1500, 2200, 20, 5, 'flacon'],
            ['Cosmetiques', 'Huile corporelle 250ml', 'CM-SUP-060', 1100, 1600, 22, 5, 'flacon'],
        ];

        foreach ($products as $productData) {
            [$category, $name, $sku, $purchasePrice, $sellingPrice, $quantity, $alertQuantity, $unit, $barcode] = array_pad($productData, 9, null);

            Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'barcode' => $barcode,
                    'description' => "Produit de supermarche courant au Cameroun.",
                    'category_id' => $categories[$category],
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'quantity' => $quantity,
                    'alert_quantity' => $alertQuantity,
                    'unit' => $unit,
                    'is_active' => true,
                    'created_by' => $manager->id,
                ]
            );
        }

        $this->command?->info('60 produits de supermarche camerounais charges.');
    }
}
