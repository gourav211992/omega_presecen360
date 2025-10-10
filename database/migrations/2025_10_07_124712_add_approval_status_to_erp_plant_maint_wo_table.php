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
        Schema::table('erp_plant_maint_wo', function (Blueprint $table) {
            $table->string('approvalStatus', 50)->nullable()->after('document_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_plant_maint_wo', function (Blueprint $table) {
            $table->dropColumn('approvalStatus');
        });
    }
};
