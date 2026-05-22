<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id('vehicle_id');
            $table->unsignedBigInteger('applicant_id');
            $table->string('regNumber', 50);
            $table->string('make', 50);
            $table->string('owner', 100);
            $table->text('address');
            $table->string('PlateNumber', 20);
            $table->dateTime('registration_date')->useCurrent();
            $table->string('disk_number', 50)->nullable()->unique();
            $table->dateTime('last_updated')->useCurrent()->useCurrentOnUpdate();
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            
            $table->foreign('applicant_id')
                  ->references('applicant_id')
                  ->on('applicants')
                  ->onDelete('cascade');
                  
            $table->index('applicant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
