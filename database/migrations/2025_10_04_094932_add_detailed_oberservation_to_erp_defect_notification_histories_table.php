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
            $table->text('detailed_oberservation')->nullable()->after('report_date_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_defect_notification_histories', function (Blueprint $table) {
            $table->dropColumn('detailed_oberservation');
        });
    }
};
