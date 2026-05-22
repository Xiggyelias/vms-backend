<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unregistered_plates', function (Blueprint $table) {
            $table->id();
            $table->string('plate_number');
            $table->timestamp('detected_at')->useCurrent();
            
            $table->index('plate_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unregistered_plates');
    }
};
