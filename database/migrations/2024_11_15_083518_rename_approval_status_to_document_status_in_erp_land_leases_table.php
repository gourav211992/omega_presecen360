<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('erp_land_leases', function (Blueprint $table) {
            $table->string('document_status')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('erp_land_leases', function (Blueprint $table) {
            $table->dropColumn('document_status');
        });
    }
};
