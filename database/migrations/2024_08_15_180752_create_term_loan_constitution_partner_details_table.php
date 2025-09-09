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
        Schema::create('erp_term_loan_constitution_partner_details', function (Blueprint $table) {
            $table->id();
            $table->integer('term_loan_constitution_id');
            $table->string('name')->nullable();
            $table->string('age')->nullable();
            $table->string('position')->nullable();
            $table->string('shareholding')->nullable();
            $table->string('percentage')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_term_loan_constitution_partner_details');
    }
};
