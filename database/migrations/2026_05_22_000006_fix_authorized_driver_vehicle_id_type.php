<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The original migration created vehicle_id as unsignedInteger (32-bit)
        // but vehicles.vehicle_id is a bigInteger (64-bit) primary key via id().
        // MySQL silently refused to create the FK; SQLite has no strict FK types.
        // Fix: convert to unsignedBigInteger, nullable (rows with vehicle_id=0
        // were already set to NULL by the comprehensive fixes migration), then
        // add the proper foreign key constraint.

        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN — recreate via raw statement.
            // In SQLite foreign key types are not enforced, so this is cosmetic.
            // The FK was added by the comprehensive migration; nothing more needed.
            return;
        }

        // Drop existing vehicle_id FK if it exists (check before dropping)
        $existingFks = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'authorized_driver'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        "))->pluck('CONSTRAINT_NAME');

        if ($existingFks->contains('authorized_driver_vehicle_id_foreign')) {
            DB::statement('ALTER TABLE authorized_driver DROP FOREIGN KEY `authorized_driver_vehicle_id_foreign`');
        }
        if ($existingFks->contains('fk_authorized_driver_vehicle')) {
            DB::statement('ALTER TABLE authorized_driver DROP FOREIGN KEY `fk_authorized_driver_vehicle`');
        }

        // Drop the vehicle_id index if it exists
        $existingIndexes = collect(DB::select("
            SHOW INDEX FROM authorized_driver WHERE Column_name = 'vehicle_id'
        "))->pluck('Key_name');

        foreach ($existingIndexes->unique() as $idx) {
            DB::statement("ALTER TABLE authorized_driver DROP INDEX `{$idx}`");
        }

        // Alter the column type
        DB::statement('ALTER TABLE authorized_driver MODIFY COLUMN vehicle_id BIGINT UNSIGNED NULL');

        // Re-add FK and index if not already present
        $fksNow = collect(DB::select("
            SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'authorized_driver'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        "))->pluck('CONSTRAINT_NAME');

        if (!$fksNow->contains('fk_authorized_driver_vehicle')) {
            Schema::table('authorized_driver', function (Blueprint $table) {
                $table->foreign('vehicle_id', 'fk_authorized_driver_vehicle')
                      ->references('vehicle_id')
                      ->on('vehicles')
                      ->onDelete('cascade');
            });
        }

        $idxNow = collect(DB::select("SHOW INDEX FROM authorized_driver WHERE Key_name = 'idx_authorized_driver_vehicle'"));
        if ($idxNow->isEmpty()) {
            Schema::table('authorized_driver', function (Blueprint $table) {
                $table->index('vehicle_id', 'idx_authorized_driver_vehicle');
            });
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('authorized_driver', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_authorized_driver_vehicle');
            } catch (\Throwable) {}
            try {
                $table->dropIndex('idx_authorized_driver_vehicle');
            } catch (\Throwable) {}
        });

        DB::statement('ALTER TABLE authorized_driver MODIFY COLUMN vehicle_id INT UNSIGNED NOT NULL');
    }
};
