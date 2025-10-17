<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_advance_payment_vouchers_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->unsignedBigInteger('book_id');
            $table->string('bookCode');
            $table->integer('voucher_no');
            $table->string('document_type');
            $table->date('date');
            $table->string('payment_type');
            $table->unsignedInteger('bank_id')->nullable();
            $table->string('bankCode')->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('reference_no')->nullable();
            $table->unsignedInteger('account_id')->nullable();
            $table->string('accountNo')->nullable();
            $table->unsignedBigInteger('ledger_id')->nullable();
            $table->unsignedInteger('currency_id');
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->string('currencyCode');
            $table->unsignedInteger('org_currency_id')->nullable();
            $table->string('org_currency_code')->nullable();
            $table->string('org_currency_exg_rate')->nullable();
            $table->unsignedInteger('comp_currency_id')->nullable();
            $table->string('comp_currency_code')->nullable();
            $table->string('comp_currency_exg_rate')->nullable();
            $table->unsignedInteger('group_currency_id')->nullable();
            $table->string('group_currency_code')->nullable();
            $table->string('group_currency_exg_rate')->nullable();
            $table->string('document')->nullable();
            $table->string('remarks')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date')->nullable();
            $table->integer('approvalLevel')->default(1);
            $table->string('approvalStatus');
            $table->integer('revision_number')->default(0);
            $table->date('revision_date')->nullable();
            $table->unsignedBigInteger('location')->nullable();
            $table->unsignedInteger('user_id');
            $table->string('user_type');
            $table->unsignedInteger('group_id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('organization_id');
            $table->timestamps();
            $table->date('document_date');
            $table->enum('doc_number_type', ['Auto', 'Manually'])->nullable();
            $table->enum('doc_reset_pattern', ['Never', 'Yearly', 'Quarterly', 'Monthly']);
            $table->string('doc_prefix')->nullable();
            $table->string('doc_suffix')->nullable();
            $table->integer('doc_no')->nullable();
            $table->unsignedBigInteger('ledger_group_id')->nullable();
            $table->string('document_status')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->integer('approval_level')->nullable();

            // Indexes
            $table->index(['location']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_advance_payment_vouchers_history');
    }
};
