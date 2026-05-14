<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_a_complete_demo_dataset(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);
        $this->seed(DemoDataSeeder::class);

        $superAdmin = User::where('email', 'nanguefyllias@gmai.com')->firstOrFail();
        $manager = User::where('email', 'bertholfyllias200@gmail.com')->firstOrFail();
        $seller = User::where('email', 'grecdada@gmail.com')->firstOrFail();

        $this->assertTrue($superAdmin->hasRole('super_admin'));
        $this->assertTrue($manager->hasRole('manager'));
        $this->assertTrue($seller->hasRole('seller'));
        $this->assertSame($manager->id, $seller->created_by);
        $this->assertTrue(Hash::check('Password@123', $seller->password));

        $this->assertGreaterThanOrEqual(3, Category::where('created_by', $manager->id)->count());
        $this->assertGreaterThanOrEqual(3, Product::where('created_by', $manager->id)->count());
        $this->assertGreaterThanOrEqual(1, Sale::where('seller_id', $seller->id)->count());
        $this->assertGreaterThanOrEqual(3, StockMovement::count());
    }
}
