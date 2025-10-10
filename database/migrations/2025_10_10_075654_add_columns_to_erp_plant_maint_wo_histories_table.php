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
        Schema::table('erp_plant_maint_wo_histories', function (Blueprint $table) {
            $table->string('book_code', 50)->nullable();
            $table->unsignedBigInteger('defect_notification_id')->nullable();
            $table->unsignedBigInteger('maintenance_type_id')->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->longText('spare_parts')->nullable();
            $table->longText('checklist_data')->nullable();
            $table->longText('equipment_details')->nullable();
            $table->text('final_remark')->nullable();
            $table->string('upload_file', 255)->nullable();
            $table->string('approvalStatus',50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_plant_maint_wo_histories', function (Blueprint $table) {
            $table->dropColumn([
                'type',
                'book_code',
                'defect_notification_id',
                'equipment_id',
                'maintenance_type_id',
                'reference_type',
                'location_id',
                'spare_parts',
                'checklist_data',
                'equipment_details',
                'final_remark',
                'upload_file'
            ]);
        });
    }
};
