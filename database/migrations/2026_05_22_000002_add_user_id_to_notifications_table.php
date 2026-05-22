<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('notifications', 'link')) {
                $table->string('link', 500)->nullable()->after('type');
            }

            // Add FK only if it doesn't exist yet
            $fks = collect(\DB::select("
                SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'notifications'
                  AND COLUMN_NAME = 'user_id'
                  AND REFERENCED_TABLE_NAME = 'applicants'
                  AND TABLE_SCHEMA = DATABASE()
            "))->pluck('CONSTRAINT_NAME');

            if ($fks->isEmpty()) {
                $table->foreign('user_id')
                      ->references('applicant_id')
                      ->on('applicants')
                      ->onDelete('cascade');
            }

            // Add index only if it doesn't exist
            $indexes = collect(\DB::select("SHOW INDEX FROM notifications WHERE Key_name = 'idx_notifications_user_read'"));
            if ($indexes->isEmpty()) {
                $table->index(['user_id', 'is_read'], 'idx_notifications_user_read');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex('idx_notifications_user_read');
            $table->dropColumn(['user_id', 'link']);
        });
    }
};
