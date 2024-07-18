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
        Schema::create('shippingclasses', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('ship_class');
            $table->integer('class_slug');
            $table->string('class_desc');
            $table->integer('product_count');
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
        Schema::dropIfExists('shippingclasses');
    }
};
