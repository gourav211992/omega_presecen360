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
        Schema::table('erp_defect_notification_histories', function (Blueprint $table) {
            $table->text('attachment')->nullable()->after('report_date_time');
            $table->text('upload_document')->nullable()->after('attachment');
            $table->text('final_remarks')->nullable()->after('upload_document');
            $table->string('reference_number')->nullable()->after('final_remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_defect_notification_histories', function (Blueprint $table) {
            $table->dropColumn(['attachment', 'upload_document', 'final_remarks', 'reference_number']);
        });
    }
};
