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
        // Add soft deletes to applicants table
        Schema::table('applicants', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('status')->default('active')->after('licenseDate');
        });

        // Add soft deletes to vehicles table
        Schema::table('vehicles', function (Blueprint $table) {
            $table->softDeletes();
            $table->string('model')->nullable()->after('make');
        });

        // Add soft deletes to authorized_driver table
        Schema::table('authorized_driver', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to admin_reports table
        Schema::table('admin_reports', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to notifications table
        Schema::table('notifications', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to search_logs table
        Schema::table('search_logs', function (Blueprint $table) {
            $table->softDeletes();
        });

        // Add soft deletes to registration_drafts table
        Schema::table('registration_drafts', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Remove soft deletes from applicants table
        Schema::table('applicants', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('status');
        });

        // Remove soft deletes from vehicles table
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('model');
        });

        // Remove soft deletes from authorized_driver table
        Schema::table('authorized_driver', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from admin_reports table
        Schema::table('admin_reports', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from notifications table
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from search_logs table
        Schema::table('search_logs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Remove soft deletes from registration_drafts table
        Schema::table('registration_drafts', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};











