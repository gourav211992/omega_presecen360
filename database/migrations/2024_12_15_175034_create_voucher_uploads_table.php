<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('erp_voucher_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('book_type_id');
            $table->date('document_date');
            $table->unsignedBigInteger('location');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            
            $table->unsignedBigInteger('ledger_id')->nullable();
            $table->string('ledger_code')->nullable();
            $table->string('ledger_name')->nullable();
            
            $table->unsignedBigInteger('group_id')->nullable();
            $table->string('group_name')->nullable();
            
            $table->decimal('debit_amount', 15, 2)->default(0.00);
            $table->decimal('credit_amount', 15, 2)->default(0.00);
            
            $table->decimal('debit_amount_org', 15, 2)->default(0.00);
            $table->decimal('credit_amount_org', 15, 2)->default(0.00);
            
            $table->decimal('debit_amount_comp', 15, 2)->default(0.00);
            $table->decimal('credit_amount_comp', 15, 2)->default(0.00);
            
            $table->decimal('debit_amount_group', 15, 2)->default(0.00);
            $table->decimal('credit_amount_group', 15, 2)->default(0.00);
            
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->string('cost_center_name')->nullable();
            
            $table->text('remark')->nullable();
            
            $table->unsignedBigInteger('org_currency_id')->nullable();
            $table->unsignedBigInteger('comp_currency_id')->nullable();
            $table->unsignedBigInteger('group_currency_id')->nullable();
            
            $table->decimal('org_exchange_rate', 10, 4)->default(1.0000);
            $table->decimal('comp_exchange_rate', 10, 4)->default(1.0000);
            $table->decimal('group_exchange_rate', 10, 4)->default(1.0000);
            
            $table->integer('row_number');
            $table->json('reason')->nullable();
            $table->tinyInteger('migrate_status')->default(0); // 0 = ready, 1 = error
            
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('organization_id');
            
            $table->timestamps();
            
            $table->index(['created_by', 'migrate_status']);
            $table->index(['organization_id']);
            $table->index(['voucher_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('erp_voucher_uploads');
    }
};
