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
        if (!Schema::hasColumn('orders', 'group_title')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('group_title')->nullable()->after('label_url');
            });
        }

        if (!Schema::hasColumn('orders', 'carrier_label')) {
            Schema::table('orders', function (Blueprint $table) {
                $after = Schema::hasColumn('orders', 'group_title') ? 'group_title' : 'label_url';
                $table->string('carrier_label')->nullable()->after($after);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('orders', 'carrier_label')) {
                $columns[] = 'carrier_label';
            }
            if (Schema::hasColumn('orders', 'group_title')) {
                $columns[] = 'group_title';
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
