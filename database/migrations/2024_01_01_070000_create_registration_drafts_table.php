<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('applicant_id')->unique();
            $table->longText('draft_data');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->foreign('applicant_id')
                  ->references('applicant_id')
                  ->on('applicants')
                  ->onDelete('cascade');
                  
            $table->index('applicant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_drafts');
    }
};
