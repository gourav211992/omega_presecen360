<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_staging_furbooks_ledger', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('location_id');
            $table->unsignedBigInteger('organization_id');
            $table->string('currency_code');
            $table->string('furbooks_code');
            $table->string('cost_center')->nullable();
            $table->text('remark')->nullable();
            $table->text('final_remark')->nullable();
            $table->date('document_date')->nullable();
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_staging_furbooks_ledger');
    }
};
