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
        Schema::table('erp_plant_maint_wo', function (Blueprint $table) {
            $table->unsignedBigInteger('equipment_id')
            ->nullable()
            ->after('book_id')
            ->index();

            $table->unsignedBigInteger('maintenance_type_id')
            ->nullable()
            ->after('equipment_id')
            ->index();

            $table->string('reference_type')
            ->nullable()
            ->after('maintenance_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_plant_maint_wo', function (Blueprint $table) {
            $table->dropColumn('equipment_id');
            $table->dropColumn('maintenance_type_id');
            $table->dropColumn('reference_type');
        });
    }
};
