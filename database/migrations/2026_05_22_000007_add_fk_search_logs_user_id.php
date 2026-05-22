<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support adding foreign keys to existing tables
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Ensure column type matches the PK (applicants.applicant_id is bigint unsigned)
        DB::statement('ALTER TABLE search_logs MODIFY COLUMN user_id BIGINT UNSIGNED NOT NULL');

        // Orphan rows would violate the FK — remove any that reference a deleted applicant
        DB::table('search_logs')
            ->whereNotIn('user_id', DB::table('applicants')->select('applicant_id'))
            ->delete();

        // Only add index/FK if not already present
        $hasIndex = !empty(DB::select("SHOW INDEX FROM search_logs WHERE Key_name = 'search_logs_user_id_idx'"));
        $hasFk = !empty(DB::select("
            SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'search_logs'
              AND CONSTRAINT_NAME = 'search_logs_user_id_fk'
        "));

        Schema::table('search_logs', function (Blueprint $table) use ($hasIndex, $hasFk) {
            if (!$hasIndex) {
                $table->index('user_id', 'search_logs_user_id_idx');
            }
            if (!$hasFk) {
                $table->foreign('user_id', 'search_logs_user_id_fk')
                      ->references('applicant_id')
                      ->on('applicants')
                      ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('search_logs', function (Blueprint $table) {
            $table->dropForeign('search_logs_user_id_fk');
            $table->dropIndex('search_logs_user_id_idx');
        });
    }
};
