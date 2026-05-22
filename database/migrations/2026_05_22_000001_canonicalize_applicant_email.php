<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('applicants')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('applicants', 'email')) {
                $table->string('email', 255)->nullable()->after('Email');
            }
        });

        DB::statement("UPDATE applicants SET email = Email WHERE (email IS NULL OR email = '') AND Email IS NOT NULL AND Email <> ''");

        // Normalize casing for canonical email matching.
        DB::statement("UPDATE applicants SET email = LOWER(email) WHERE email IS NOT NULL AND email <> ''");

        Schema::table('applicants', function (Blueprint $table) {
            try {
                $table->index('email', 'idx_applicants_email_canonical');
            } catch (\Throwable $e) {
                // Index may already exist.
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('applicants')) {
            return;
        }

        Schema::table('applicants', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_applicants_email_canonical');
            } catch (\Throwable $e) {
                // No-op if index does not exist.
            }
        });
    }
};

