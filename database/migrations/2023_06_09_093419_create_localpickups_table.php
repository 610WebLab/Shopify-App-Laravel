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
        Schema::create('localpickups', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('zone_id');
            $table->integer('shipping_method_id');
            $table->string('title');
            $table->string('tax_status')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->integer('pickup_cost')->nullable();
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
        Schema::dropIfExists('localpickups');
    }
};
