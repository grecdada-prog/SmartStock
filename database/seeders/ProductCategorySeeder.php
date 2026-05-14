<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::role('super_admin')->orderBy('id')->first();

        if (!$owner) {
            $this->command?->warn('Aucun superadmin trouvé. Lancez RolePermissionSeeder avant ProductCategorySeeder.');

            return;
        }

        $categories = [
            ['Alimentation générale', 'Produits courants vendus en boutique, alimentation de base et articles de consommation rapide.'],
            ['Riz, pâtes et céréales', 'Riz parfumé, riz cassé, spaghetti, macaroni, couscous, farine, maïs, mil et céréales.'],
            ['Huiles, sauces et condiments', 'Huiles de cuisine, tomate, mayonnaise, arômes, cubes, sel, poivre et assaisonnements.'],
            ['Boissons et eaux', 'Eaux minérales, jus, sodas, boissons énergétiques, malt, sirops et boissons non alcoolisées.'],
            ['Brasseries et boissons alcoolisées', 'Bières, vins, spiritueux et boissons alcoolisées, selon autorisation du commerce.'],
            ['Produits laitiers', 'Lait en poudre, lait concentré, yaourts, beurre, margarine et fromages.'],
            ['Boulangerie et biscuits', 'Pain, pain de mie, biscuits, madeleines, gâteaux, beignets emballés et snacks sucrés.'],
            ['Conserves et boîtes', 'Sardines, thon, tomates, maïs, haricots, petits pois et autres boîtes de conserve.'],
            ['Produits frais et vivres', 'Plantain, manioc, macabo, igname, fruits, légumes et autres vivres frais.'],
            ['Viandes, poissons et surgelés', 'Poulet, viande, poisson fumé ou congelé, crevettes, frites et produits surgelés.'],
            ['Bonbons, snacks et amuse-gueules', 'Bonbons, chewing-gums, arachides, chips, biscuits salés et petits snacks.'],
            ['Hygiène corporelle', 'Savons, dentifrices, brosses à dents, papiers hygiéniques, gels douche et serviettes.'],
            ['Cosmétiques et beauté', 'Laits de toilette, crèmes, huiles corporelles, parfums, déodorants, shampoings et mèches.'],
            ['Entretien maison', 'Lessive, détergents, eau de javel, liquide vaisselle, insecticides, serpillières et éponges.'],
            ['Bébé et maternité', 'Couches, lingettes, lait infantile, savon bébé, pommades et accessoires bébé.'],
            ['Pharmacie et premiers soins', 'Parapharmacie, pansements, antiseptiques, consommables santé et premiers soins.'],
            ['Papeterie et scolaire', 'Cahiers, stylos, crayons, papiers, cartables, fournitures scolaires et bureautiques.'],
            ['Téléphonie et accessoires', 'Chargeurs, câbles, écouteurs, pochettes, protections écran, cartes SIM et accessoires.'],
            ['Électricité et électronique', 'Ampoules, piles, multiprises, rallonges, petits appareils et accessoires électroniques.'],
            ['Cuisine et maison', 'Vaisselle, marmites, ustensiles, seaux, bassines, rangements et articles de maison.'],
            ['Quincaillerie et bricolage', 'Outils, vis, clous, colles, cadenas, serrures, peintures et petits matériels.'],
            ['Textile, chaussures et sacs', 'Vêtements, sous-vêtements, chaussures, sacs, pagnes et accessoires vestimentaires.'],
            ['Gaz, charbon et combustibles', 'Gaz domestique, charbon, pétrole, allume-feu et accessoires de cuisson.'],
            ['Auto, moto et lubrifiants', 'Huiles moteur, accessoires moto, nettoyants, pièces simples et consommables auto.'],
            ['Agriculture et élevage', 'Semences, engrais, aliments animaux, produits agricoles et petits équipements.'],
            ['Divers et services', 'Articles spéciaux, prestations, services, produits saisonniers et catégories ponctuelles.'],
        ];

        foreach ($categories as [$name, $description]) {
            Category::updateOrCreate(
                ['name' => $name, 'created_by' => $owner->id],
                [
                    'description' => $description,
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info(count($categories) . ' catégories produits chargées.');
    }
}
