<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Ensure applicants.email is lowercase and populated ─────────────
        // Use raw INFORMATION_SCHEMA to avoid case-insensitive column name confusion on Windows.
        $emailCols = collect(DB::select("
            SELECT LOWER(COLUMN_NAME) as col
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'applicants'
              AND LOWER(COLUMN_NAME) = 'email'
        "))->pluck('col');

        if ($emailCols->isEmpty()) {
            // No email column at all — add it
            Schema::table('applicants', function (Blueprint $table) {
                $table->string('email', 255)->nullable()->after('phone');
            });
        }

        // Normalize: convert empty strings to NULL, then lowercase
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE applicants SET email = NULL WHERE email = ''");
            DB::statement('UPDATE applicants SET email = LOWER(TRIM(email)) WHERE email IS NOT NULL');
        }

        // ── 2. UNIQUE on applicants.email ─────────────────────────────────────
        Schema::table('applicants', function (Blueprint $table) {
            if (!$this->indexExists('applicants', 'applicants_email_unique')) {
                $table->unique('email', 'applicants_email_unique');
            }
        });

        // ── 3. UNIQUE on vehicles.PlateNumber ─────────────────────────────────
        if (!$this->indexExists('vehicles', 'vehicles_plate_number_unique')) {
            if (DB::getDriverName() === 'mysql') {
                // Make nullable first so we can null out empty strings
                DB::statement('ALTER TABLE vehicles MODIFY COLUMN PlateNumber VARCHAR(20) NULL');
                DB::statement("UPDATE vehicles SET PlateNumber = NULL WHERE PlateNumber = ''");
                DB::statement('UPDATE vehicles SET PlateNumber = UPPER(TRIM(PlateNumber)) WHERE PlateNumber IS NOT NULL');
            }
            Schema::table('vehicles', function (Blueprint $table) {
                $table->unique('PlateNumber', 'vehicles_plate_number_unique');
            });
        }

        // ── 4. UNIQUE on vehicles.regNumber ───────────────────────────────────
        if (!$this->indexExists('vehicles', 'vehicles_reg_number_unique')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE vehicles MODIFY COLUMN regNumber VARCHAR(50) NULL');
                DB::statement("UPDATE vehicles SET regNumber = NULL WHERE regNumber = ''");
            }
            Schema::table('vehicles', function (Blueprint $table) {
                $table->unique('regNumber', 'vehicles_reg_number_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropUnique('vehicles_plate_number_unique');
            $table->dropUnique('vehicles_reg_number_unique');
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->dropUnique('applicants_email_unique');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select("
            SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
        ", [$table, $indexName]);

        return !empty($rows);
    }
};
