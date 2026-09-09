<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('rates_by_distances', function (Blueprint $table) {
            $table->dropColumn([
                'location_name',
                'country_region',
                'city',
                'street',
                'postal_code',
                'set_latitude_longitude',
                'latitude',
                'longitude',
            ]);
        });
    }

    public function down()
    {
        Schema::table('rates_by_distances', function (Blueprint $table) {
            $table->string('location_name')->nullable()->after('description');
            $table->string('country_region')->nullable()->after('location_name');
            $table->string('city')->nullable()->after('country_region');
            $table->string('street')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('street');
            $table->enum('set_latitude_longitude', ['yes', 'no'])->default('no')->after('postal_code');
            $table->decimal('latitude', 10, 7)->nullable()->after('max_delivery_rate');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }
};
