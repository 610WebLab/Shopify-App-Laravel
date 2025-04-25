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
        Schema::create('other_carrier_services', function (Blueprint $table) {
            $table->id();
            $table->string('carrier_type');
            $table->bigInteger('user_id');
            $table->string('api_key')->nullable();
            $table->string("secret_key")->nullable();
            $table->string("token")->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
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
        Schema::dropIfExists('other_carrier_services');
    }
};
