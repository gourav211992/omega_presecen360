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
        Schema::table('erp_hsns', function (Blueprint $table) {
            $table->dropColumn(['valid_from', 'valid_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_hsns', function (Blueprint $table) {
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
        });
    }
};
