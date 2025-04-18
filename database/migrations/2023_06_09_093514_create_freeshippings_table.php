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
        Schema::create('freeshippings', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('zone_id');
            $table->integer('shipping_method_id');
            $table->string('title');
            $table->enum('ship_require', ['none','min_amount', 'coupon', 'either','both']);
            $table->float('order_amount');
            $table->float('discount');
            $table->tinyInteger('status')->default(1);
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
        Schema::dropIfExists('freeshippings');
    }
};
