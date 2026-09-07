<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        if (Schema::hasColumn('orders', 'labels')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('labels');
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
        if (!Schema::hasColumn('orders', 'labels')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('labels')->nullable()->after('order_no');
            });
        }
    }
};
