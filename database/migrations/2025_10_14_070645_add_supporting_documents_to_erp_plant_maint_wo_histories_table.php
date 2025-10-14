<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_plant_maint_wo_histories', function (Blueprint $table) {
            $table->string('supporting_documents', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('erp_plant_maint_wo_histories', function (Blueprint $table) {
            $table->dropColumn('supporting_documents');
        });
    }
};
