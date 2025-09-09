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
        Schema::table('erp_customers', function (Blueprint $table) {
            $table->unsignedBigInteger('enter_company_org_id')->nullable()->after('company_id')->index();
        });

        Schema::table('erp_vendors', function (Blueprint $table) {
            $table->unsignedBigInteger('enter_company_org_id')->nullable()->after('company_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_customers', function (Blueprint $table) {
            $table->dropColumn('enter_company_org_id');
        });


        Schema::table('erp_vendors', function (Blueprint $table) {
            $table->dropColumn('enter_company_org_id');
        });
    }
};
