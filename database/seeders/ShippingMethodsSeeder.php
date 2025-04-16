<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShippingMethodsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('shippingmethods')->insert([
            [
                'ship_method' => 'flat_rate',
                'ship_desc' => 'Lets you charge a fixed rate for shipping.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ship_method' => 'free_shipping',
                'ship_desc' => 'Free shipping is a special method which can be triggered with coupons and minimum spends.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ship_method' => 'local_pickup',
                'ship_desc' => 'Allow customers to pick up orders themselves. By default, when using local pickup store base taxes will apply regardless of customer address.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ship_method' => 'table_rate',
                'ship_desc' => 'Table rates are dynamic rates based on a number of cart conditions.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'ship_method' => 'rates_by_distance',
                'ship_desc' => "Rates by Distance rates are shipping fees dynamically calculated based on the distance between your shipping origin and the customer's delivery address.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
