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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->nullable();
            $table->bigInteger('order_id')->nullable();
            $table->string('order_name')->nullable();
            $table->bigInteger('order_no')->nullable();
            $table->string('labels')->nullable();
            $table->timestamp('date')->nullable();
            $table->bigInteger('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->double('total',15,2)->default(0);
            $table->string('payment_status')->nullable();
            $table->string('fullfilement')->nullable();
            $table->integer('item_count')->nullable();
            $table->integer('label_count')->default(1);
            $table->bigInteger('dimension_id')->nullable();
            $table->string('delivery_status')->nullable();
            $table->string('delivery_method')->nullable();
            $table->longText('tags')->nullable();
            $table->longText('shipping_code')->nullable();
            $table->longText('label_url')->nullable();

            $table->bigInteger('shipping_service')->default(0);
            $table->bigInteger('carrier_id')->nullable();
            $table->bigInteger('template_id')->nullable();
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
        Schema::dropIfExists('orders');
    }
};
