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
        Schema::table('erp_tax_details', function (Blueprint $table) {
            $table->dropColumn(['tax_name', 'tax_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_tax_details', function (Blueprint $table) {
            $table->string('tax_name')->nullable();
            $table->string('tax_code')->nullable();
        });
    }
};
