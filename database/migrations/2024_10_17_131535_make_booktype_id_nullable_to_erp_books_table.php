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
        Schema::table('erp_books', function (Blueprint $table) {
            $table->unsignedBigInteger('booktype_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_books', function (Blueprint $table) {
            $table->unsignedBigInteger('booktype_id')->nullable(false)->change();
        });
    }
};
