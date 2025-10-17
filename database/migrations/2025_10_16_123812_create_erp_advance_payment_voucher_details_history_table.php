<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_advance_payment_voucher_details_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('source_id')->unsigned();
            $table->bigInteger('payment_voucher_id')->unsigned();
            $table->bigInteger('ledger_id')->unsigned()->nullable();
            $table->bigInteger('ledger_group_id')->unsigned()->nullable();
            $table->bigInteger('party_id')->unsigned()->nullable();
            $table->string('party_type', 255)->nullable();
            $table->string('type', 255);
            $table->string('reference', 255);
            $table->string('reference_no', 255)->nullable();
            $table->string('partyCode', 255);
            $table->decimal('currentAmount', 15, 2)->default(0.00);
            $table->decimal('orgAmount', 15, 2)->default(0.00);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_advance_payment_voucher_details_history');
    }
};
