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
        Schema::table('erp_vouchers', function (Blueprint $table) {
            $table->double('org_exchange_rate')->nullable()->after('currency_id'); // Replace 'column_name' with the column after which you want to add currency_id

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_vouchers', function (Blueprint $table) {
            //
        });
    }
};
