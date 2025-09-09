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
        Schema::table('erp_document_approvals', function (Blueprint $table) {
            $table->string('document_name')->nullable()->after('document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_document_approvals', function (Blueprint $table) {
            $table->dropColumn('document_name');
        });
    }
};
