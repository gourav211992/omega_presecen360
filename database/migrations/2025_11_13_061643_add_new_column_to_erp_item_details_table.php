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
        Schema::table('erp_item_details', function (Blueprint $table) {
            $table->string('reference_party_no')->nullable();
            $table->date('reference_party_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_item_details', function (Blueprint $table) {
            $table->dropColumn('reference_party_no');
            $table->dropColumn('reference_party_date');
        });
    }
};
