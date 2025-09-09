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
    Schema::table('erp_payment_vouchers', function (Blueprint $table) {
        $table->string('document_status')->after('approvalStatus')->nullable()->comment('Status of the document');
    });
}

/**
 * Reverse the migrations.
 */
public function down(): void
{
    Schema::table('erp_payment_vouchers', function (Blueprint $table) {
        $table->dropColumn('document_status');
    });
}

};
