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
        Schema::create('tablerateoptions', function (Blueprint $table) {
            $table->id();
            $table->integer('table_rate_id');
            $table->integer('ship_class');
            $table->string('condition');
            $table->integer('ship_min');
            $table->integer('ship_max');
            $table->integer('ship_break');
            $table->integer('ship_abort');
            $table->integer('row_cost');
            $table->integer('item_cost');
            $table->integer('lbs_cost');
            $table->integer('percent_cost');
            $table->string('lable')->nullable();
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
        Schema::dropIfExists('tablerateoptions');
    }
};
