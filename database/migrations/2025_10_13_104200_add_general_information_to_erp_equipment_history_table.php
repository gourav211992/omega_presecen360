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
        Schema::table('erp_equipment_history', function (Blueprint $table) {
            $table->string('model_name', 150)->nullable();
            $table->string('manufacturer_name', 150)->nullable()->after('model_name');
            $table->string('yom', 10)->nullable()->after('manufacturer_name'); // Year as string
            $table->string('commission_date', 20)->nullable()->after('yom');   // Date as string
            $table->decimal('purchase_cost', 15, 2)->nullable()->after('commission_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_equipment_history', function (Blueprint $table) {
            $table->dropColumn([
                'model_name',
                'manufacturer_name',
                'yom',
                'commission_date',
                'purchase_cost'
            ]);
        });
    }
};
