<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorized_driver', function (Blueprint $table) {
            $table->id('Id');
            $table->unsignedInteger('vehicle_id');
            $table->string('fullname', 100);
            $table->integer('licenseNumber');
            $table->string('contact', 20);
            $table->unsignedBigInteger('applicant_id')->nullable();

            $table->index('vehicle_id', 'index');
            
            $table->foreign('applicant_id', 'fk_applicant')
                  ->references('applicant_id')
                  ->on('applicants')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('authorized_driver');
    }
};
