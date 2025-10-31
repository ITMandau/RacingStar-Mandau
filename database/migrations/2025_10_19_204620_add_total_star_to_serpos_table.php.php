<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('serpos') &&
            ! Schema::hasColumn('serpos', 'total_star')) {

            Schema::table('serpos', function (Blueprint $t) {
                $t->integer('total_star')->nullable()->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('serpos') &&
            Schema::hasColumn('serpos', 'total_star')) {

            Schema::table('serpos', function (Blueprint $t) {$t->dropColumn('total_star'); });
        }
    }
};
