<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('other_carriers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id'); // Foreign key for shops
            $table->string('carrier_name'); // Carrier Name
            $table->bigInteger('account_id')->unsigned(); // Account ID
            $table->string('api_key')->unique(); // API Key
            $table->enum('status', ['active', 'inactive'])->default('active'); // Status
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('other_carriers');
    }
};

