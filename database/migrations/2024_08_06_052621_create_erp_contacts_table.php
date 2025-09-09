<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Helpers\ConstantHelper;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('erp_contacts', function (Blueprint $table) {
            $table->id(); 
            $table->boolean('primary')->default(false);
            $table->enum('salutation',ConstantHelper::TITLES)->nullable()->index();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index(); 
            $table->string('mobile')->nullable()->index(); 
            $table->string('phone')->nullable()->index(); 
            $table->morphs('contactable');
            $table->enum('status', ConstantHelper::STATUS)->default(ConstantHelper::ACTIVE);
            $table->timestamps(); 
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_contacts');
    }
};
