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
        Schema::create('tablerates', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('zone_id');
            $table->integer('shipping_method_id');
            $table->string('title');
            $table->string('description')->nullable();
            $table->string('tax_status');
            $table->string('taxship_include');
            $table->float('handling_fee');
            $table->float('maxship_cost');
            $table->string('calculation_type');
            $table->float('handlingfee_peritem');
            $table->float('mincost_peritem');
            $table->float('maxcost_peritem');
            $table->float('dicount_minmax');
            $table->float('tax_minmax');
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
        Schema::dropIfExists('tablerates');
    }
};
