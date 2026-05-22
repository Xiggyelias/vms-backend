<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Fix licenseNumber column type from INT to VARCHAR
        Schema::table('authorized_driver', function (Blueprint $table) {
            $table->string('licenseNumber', 50)->change();
        });

        // Add composite indexes for better performance
        Schema::table('vehicles', function (Blueprint $table) {
            // Index for common queries: applicant + status
            $table->index(['applicant_id', 'status'], 'idx_applicant_status');

            // Index for search queries: regNumber + status
            $table->index(['regNumber', 'status'], 'idx_regnumber_status');

            // Index for plate number searches
            $table->index('PlateNumber', 'idx_plate_number');

            // Index for date range queries
            $table->index('registration_date', 'idx_registration_date');
        });

        // Add indexes for authorized drivers
        Schema::table('authorized_driver', function (Blueprint $table) {
            // Composite index for common queries
            $table->index(['applicant_id', 'vehicle_id'], 'idx_applicant_vehicle');

            // Index for license number searches
            $table->index('licenseNumber', 'idx_license_number');
        });

        // Add indexes for applicants
        Schema::table('applicants', function (Blueprint $table) {
            // Composite index for authentication queries
            $table->index(['registrantType', 'Email'], 'idx_type_email');

            // Index for Google authentication
            if (Schema::hasColumn('applicants', 'google_id')) {
                $table->index('google_id', 'idx_google_id');
            }

            // Index for last login queries
            $table->index('last_login', 'idx_last_login');
        });

        // Add indexes for search logs
        Schema::table('search_logs', function (Blueprint $table) {
            // Composite index for user searches
            $table->index(['user_id', 'search_date'], 'idx_user_search_date');

            // Index for search term analysis
            $table->index(['search_type', 'search_date'], 'idx_type_date');
        });

        // Add indexes for notifications
        Schema::table('notifications', function (Blueprint $table) {
            // Index for unread notifications
            $table->index(['is_read', 'created_at'], 'idx_read_created');

            // Index for type-based queries
            $table->index('type', 'idx_notification_type');
        });

        // Add indexes for reports
        Schema::table('admin_reports', function (Blueprint $table) {
            // Index for admin queries
            $table->index(['admin_id', 'created_at'], 'idx_admin_created');

            // Index for category queries
            $table->index(['category', 'report_date'], 'idx_category_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Remove indexes
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex('idx_applicant_status');
            $table->dropIndex('idx_regnumber_status');
            $table->dropIndex('idx_plate_number');
            $table->dropIndex('idx_registration_date');
        });

        Schema::table('authorized_driver', function (Blueprint $table) {
            $table->dropIndex('idx_applicant_vehicle');
            $table->dropIndex('idx_license_number');
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->dropIndex('idx_type_email');
            if (Schema::hasColumn('applicants', 'google_id')) {
                $table->dropIndex('idx_google_id');
            }
            $table->dropIndex('idx_last_login');
        });

        Schema::table('search_logs', function (Blueprint $table) {
            $table->dropIndex('idx_user_search_date');
            $table->dropIndex('idx_type_date');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_read_created');
            $table->dropIndex('idx_notification_type');
        });

        Schema::table('admin_reports', function (Blueprint $table) {
            $table->dropIndex('idx_admin_created');
            $table->dropIndex('idx_category_date');
        });

        // Note: Not reverting licenseNumber back to INT as VARCHAR is more appropriate
    }
};











