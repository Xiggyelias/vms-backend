<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id('applicant_id');
            $table->enum('registrantType', ['student', 'staff', 'guest']);
            $table->string('studentRegNo', 50)->nullable();
            $table->string('staffsRegNo', 50)->nullable();
            $table->string('fullName', 100);
            $table->string('password', 225);
            $table->string('phone', 20);
            $table->string('email', 255);
            $table->string('college', 100);
            $table->string('idNumber', 50);
            $table->string('licenseNumber', 50);
            $table->string('licenseClass', 10);
            $table->date('licenseDate');
            
            $table->index('registrantType');
            $table->index('studentRegNo');
            $table->index('staffsRegNo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};
