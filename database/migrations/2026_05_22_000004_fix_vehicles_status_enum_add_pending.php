<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL only — SQLite enums are stored as TEXT so no ALTER needed
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE vehicles
                MODIFY COLUMN status ENUM('active','inactive','pending') NOT NULL DEFAULT 'inactive'
            ");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Move any 'pending' rows to 'inactive' before narrowing the enum
            DB::table('vehicles')->where('status', 'pending')->update(['status' => 'inactive']);

            DB::statement("
                ALTER TABLE vehicles
                MODIFY COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'inactive'
            ");
        }
    }
};
