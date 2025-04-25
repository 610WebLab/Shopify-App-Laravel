<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class PlansTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('plans')->insert([
            [
                'id' => 1,
                'type' => 'RECURRING',
                'name' => 'Free',
                'price' => 0.00,
                'interval' => 'EVERY_30_DAYS',
                'capped_amount' => 0.00,
                'terms' => 'Enable 0-10 locations',
                'trial_days' => 0,
                'test' => 0,
                'on_install' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 2,
                'type' => 'RECURRING',
                'name' => 'Essentials',
                'price' => 10.00,
                'interval' => 'EVERY_30_DAYS',
                'capped_amount' => 0.00,
                'terms' => 'Enable 10-100 locations',
                'trial_days' => 0,
                'test' => 0,
                'on_install' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 3,
                'type' => 'RECURRING',
                'name' => 'Premium',
                'price' => 20.00,
                'interval' => 'EVERY_30_DAYS',
                'capped_amount' => 0.00,
                'terms' => 'Enable 100-500 locations',
                'trial_days' => 0,
                'test' => 0,
                'on_install' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
