<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_advance_payment_voucher_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('payment_voucher_id')->index();
            $table->unsignedBigInteger('ledger_id')->nullable();
            $table->unsignedBigInteger('ledger_group_id')->nullable();
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('party_type')->nullable();
            $table->string('type');
            $table->string('reference');
            $table->string('reference_no')->nullable();
            $table->string('partyCode');
            $table->decimal('currentAmount', 15, 2)->default(0.00);
            $table->decimal('orgAmount', 15, 2)->default(0.00);
            $table->timestamps();

            // Optional: Uncomment if foreign key is required
            // $table->foreign('advance_payment_voucher_id')->references('id')->on('erp_advance_payment_vouchers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_advance_payment_voucher_details');
    }
};
