<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Track when the registration expires
            $table->date('registration_expiry')->nullable()->after('registration_date');
            // Track the last renewal date separately from registration_date
            $table->date('last_renewed_at')->nullable()->after('registration_expiry');

            $table->index('registration_expiry', 'idx_reg_expiry');
        });

        // Back-fill: set expiry to registration_date + 365 days for all existing vehicles
        DB::statement("
            UPDATE vehicles
            SET registration_expiry = DATE_ADD(DATE(registration_date), INTERVAL 365 DAY)
            WHERE registration_expiry IS NULL
              AND registration_date IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex('idx_reg_expiry');
            $table->dropColumn(['registration_expiry', 'last_renewed_at']);
        });
    }
};
