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
            $table->tinyInteger('status')->nullable();;
            $table->longText('zone_region')->nullable();;
            $table->longText('country')->nullable();;
            $table->longText('state')->nullable();;
            $table->longText('zip')->nullable();
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
