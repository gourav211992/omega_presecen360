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
        Schema::table('erp_equipment_history', function (Blueprint $table) {
            if (!Schema::hasColumn('erp_equipment_history', 'asset_code_id')) {
                $table->unsignedBigInteger('asset_code_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_equipment_history', function (Blueprint $table) {
                $table->dropColumn('asset_code_id');
        });
    }
};
