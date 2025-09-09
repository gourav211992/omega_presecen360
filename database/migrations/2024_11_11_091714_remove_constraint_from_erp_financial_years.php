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
        Schema::table('erp_financial_years', function (Blueprint $table) {
            $table->dropUnique('erp_financial_years_group_id_alias_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_financial_years', function (Blueprint $table) {
            $table->unique(['group_id', 'alias']);
        });
    }
};
