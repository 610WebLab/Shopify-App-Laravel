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
        Schema::create('rates_by_distances', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('zone_id')->nullable();
            $table->bigInteger('shipping_method_id')->nullable();
            $table->string('title')->nullable();
            $table->longText('description')->nullable();
            $table->string('location_name')->nullable();
            $table->string('country_region')->nullable();
            $table->string('city')->nullable();
            $table->string('street')->nullable();
            $table->string('postal_code')->nullable();
            $table->enum('set_latitude_longitude', ['yes', 'no'])->default('no');
            $table->enum('rates', ['price_based_rate', 'weight_based_rate'])->nullable();
            $table->enum('rate_price_limit', ['yes', 'no'])->default('no');
            $table->enum('weight_unit', ['kg', 'lb','oz'])->default('kg');

            $table->decimal('max_delivery_rate', 8, 2)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('min_order_price', 8, 2)->nullable();
            $table->decimal('max_order_price', 8, 2)->nullable();
            $table->decimal('min_order_weight', 8, 2)->nullable();
            $table->decimal('max_order_weight', 8, 2)->nullable();
            $table->decimal('min_distance', 8, 2)->nullable();
            $table->decimal('max_distance', 8, 2)->nullable();
            $table->decimal('base_delivery_price', 8, 2)->nullable();
            $table->decimal('price_per_kilometer', 8, 2)->nullable();
            $table->tinyInteger('status')->nullable();
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
        Schema::dropIfExists('rates_by_distances');
    }
};
