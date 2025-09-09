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
            $table->boolean('manual_entry') ->default(1) -> after('book_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_books', function (Blueprint $table) {
            $table->dropColumn(['manual_entry']);
        });
    }
};
