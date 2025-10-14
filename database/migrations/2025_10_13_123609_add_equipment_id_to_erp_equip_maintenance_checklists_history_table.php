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
        Schema::table('erp_equip_maintenance_checklists_history', function (Blueprint $table) {
            $table->unsignedBigInteger('equipment_id')->nullable()->after('erp_equip_maintenance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_equip_maintenance_checklists_history', function (Blueprint $table) {
            $table->dropColumn('equipment_id');
        });
    }
};
