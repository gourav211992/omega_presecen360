<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\ConstantHelper;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         Schema::create('erp_finance_ledger_furbooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('status', 50)->default('active');
            $table->json('ledgers')->nullable(); // store multiple ledgers with group + furbook_code
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_ledger_furbooks');
    }
};
