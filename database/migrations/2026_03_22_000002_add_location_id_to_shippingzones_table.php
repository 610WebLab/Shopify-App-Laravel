<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shippingzones', function (Blueprint $table) {
            $table->unsignedBigInteger('location_id')->nullable()->after('zip');
            $table->foreign('location_id')
                ->references('id')
                ->on('store_locations')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('shippingzones', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
