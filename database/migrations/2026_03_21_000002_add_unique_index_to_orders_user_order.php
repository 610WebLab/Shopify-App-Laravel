<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('orders')) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'mysql') {
                DB::statement('
                    DELETE o1 FROM orders o1
                    INNER JOIN orders o2
                    ON o1.user_id = o2.user_id
                    AND o1.order_id = o2.order_id
                    AND o1.id > o2.id
                    WHERE o1.order_id IS NOT NULL
                ');
            } else {
                $duplicates = DB::table('orders')
                    ->select('user_id', 'order_id', DB::raw('MIN(id) as keep_id'))
                    ->whereNotNull('order_id')
                    ->groupBy('user_id', 'order_id')
                    ->havingRaw('COUNT(*) > 1')
                    ->get();

                foreach ($duplicates as $duplicate) {
                    DB::table('orders')
                        ->where('user_id', $duplicate->user_id)
                        ->where('order_id', $duplicate->order_id)
                        ->where('id', '!=', $duplicate->keep_id)
                        ->delete();
                }
            }

            Schema::table('orders', function (Blueprint $table) {
                $table->unique(['user_id', 'order_id'], 'orders_user_id_order_id_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_user_id_order_id_unique');
            });
        }
    }
};
