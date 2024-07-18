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
        Schema::create('shippingzones', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('ship_zone');
            $table->tinyInteger('status');
            $table->longText('zone_region');
            $table->longText('country');
            $table->longText('state');
            $table->longText('zip');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shippingzones');
    }
};
