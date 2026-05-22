<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Step 1: Fix admin table inconsistency
        $this->fixAdminTables();

        // Step 2: Add missing columns to applicants table
        $this->updateApplicantsTable();

        // Step 3: Add missing columns to vehicles table
        $this->updateVehiclesTable();

        // Step 4: Fix authorized_driver table
        $this->updateAuthorizedDriversTable();

        // Step 5: Add soft deletes to all tables
        $this->addSoftDeletes();

        // Step 6: Fix data integrity issues
        $this->fixDataIntegrity();

        // Step 7: Add all the indexes
        $this->addAllIndexes();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Remove soft deletes
        $tables = ['applicants', 'vehicles', 'authorized_driver', 'admin_reports', 'notifications', 'search_logs', 'registration_drafts'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }

        // Remove added columns
        if (Schema::hasColumn('applicants', 'status')) {
            Schema::table('applicants', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('vehicles', 'model')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('model');
            });
        }

        // Note: Not reverting licenseNumber type or removing indexes for safety
    }

    private function fixAdminTables(): void
    {
        // If both admin and admins tables exist, migrate data from admin to admins
        if (Schema::hasTable('admin') && Schema::hasTable('admins')) {
            // Copy data from admin to admins if admins is empty
            $adminCount = DB::table('admins')->count();
            if ($adminCount === 0) {
                DB::statement('INSERT INTO admins (username, password, email, created_at) SELECT username, password, email, created_at FROM admin');
            }

            // Update foreign keys in admin_reports to point to admins table
            if (Schema::hasTable('admin_reports')) {
                Schema::table('admin_reports', function (Blueprint $table) {
                    // Drop existing foreign key if it exists
                    try {
                        $table->dropForeign(['admin_id']);
                    } catch (\Exception $e) {
                        // Foreign key might not exist, continue
                    }

                    // Add correct foreign key to admins table
                    $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
                });
            }

            // Drop the old admin table
            Schema::dropIfExists('admin');
        }
    }

    private function updateApplicantsTable(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('applicants', 'google_id')) {
                $table->string('google_id', 255)->nullable()->after('email');
            }
            if (!Schema::hasColumn('applicants', 'avatar')) {
                $table->string('avatar', 255)->nullable()->after('google_id');
            }
            if (!Schema::hasColumn('applicants', 'status')) {
                $table->string('status')->default('active')->after('licenseDate');
            }
            if (!Schema::hasColumn('applicants', 'last_login')) {
                $table->timestamp('last_login')->nullable()->after('status');
            }
        });
    }

    private function updateVehiclesTable(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Add missing model column
            if (!Schema::hasColumn('vehicles', 'model')) {
                $table->string('model', 255)->nullable()->after('make');
            }
        });
    }

    private function updateAuthorizedDriversTable(): void
    {
        // Fix licenseNumber column type - use different approach for SQLite
        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            // For SQLite, we can't easily change column types, so we'll leave it as is
            // The column was already changed to VARCHAR in a previous migration
        } else {
            DB::statement('ALTER TABLE authorized_driver MODIFY COLUMN licenseNumber VARCHAR(50) NOT NULL');
        }

        // Add missing foreign key to vehicles table if it doesn't exist
        try {
            Schema::table('authorized_driver', function (Blueprint $table) {
                $table->foreign('vehicle_id')->references('vehicle_id')->on('vehicles')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Foreign key might already exist, continue
        }
    }

    private function addSoftDeletes(): void
    {
        $tables = ['applicants', 'vehicles', 'authorized_driver', 'admin_reports', 'notifications', 'search_logs'];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }

        // Handle registration_drafts table
        if (Schema::hasTable('registration_drafts') && !Schema::hasColumn('registration_drafts', 'deleted_at')) {
            Schema::table('registration_drafts', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    private function fixDataIntegrity(): void
    {
        // Fix invalid dates in applicants table
        DB::table('applicants')
            ->where('licenseDate', '0003-02-22')
            ->orWhere('licenseDate', '5465-02-23')
            ->orWhere('licenseDate', '2222-01-22')
            ->update(['licenseDate' => now()->subYears(5)->format('Y-m-d')]);

        // Fix empty required fields
        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            DB::table('applicants')
                ->where('email', '')
                ->update(['email' => DB::raw("'user_' || applicant_id || '@example.com'")]);
        } else {
            DB::table('applicants')
                ->where('email', '')
                ->update(['email' => DB::raw("CONCAT('user_', applicant_id, '@example.com')")]);
        }

        // Fix invalid license numbers in authorized_driver
        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            DB::table('authorized_driver')
                ->where('licenseNumber', 0)
                ->orWhere('licenseNumber', 2147483647)
                ->update(['licenseNumber' => DB::raw("'TEMP_' || Id")]);
        } else {
            DB::table('authorized_driver')
                ->where('licenseNumber', 0)
                ->orWhere('licenseNumber', 2147483647)
                ->update(['licenseNumber' => DB::raw("CONCAT('TEMP_', Id)")]);
        }

        // Fix vehicle_id = 0 in authorized_driver (should be nullable or valid foreign key)
        DB::table('authorized_driver')
            ->where('vehicle_id', 0)
            ->update(['vehicle_id' => null]);
    }

    private function addAllIndexes(): void
    {
        // Vehicles table indexes
        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                if (!$this->indexExists('vehicles', 'idx_applicant_status')) {
                    $table->index(['applicant_id', 'status'], 'idx_applicant_status');
                }
                if (!$this->indexExists('vehicles', 'idx_regnumber_status')) {
                    $table->index(['regNumber', 'status'], 'idx_regnumber_status');
                }
                if (!$this->indexExists('vehicles', 'idx_plate_number')) {
                    $table->index('PlateNumber', 'idx_plate_number');
                }
                if (!$this->indexExists('vehicles', 'idx_registration_date')) {
                    $table->index('registration_date', 'idx_registration_date');
                }
            });
        }

        // Authorized drivers indexes
        if (Schema::hasTable('authorized_driver')) {
            Schema::table('authorized_driver', function (Blueprint $table) {
                if (!$this->indexExists('authorized_driver', 'idx_applicant_vehicle')) {
                    $table->index(['applicant_id', 'vehicle_id'], 'idx_applicant_vehicle');
                }
                if (!$this->indexExists('authorized_driver', 'idx_license_number')) {
                    $table->index('licenseNumber', 'idx_license_number');
                }
            });
        }

        // Applicants indexes
        if (Schema::hasTable('applicants')) {
            Schema::table('applicants', function (Blueprint $table) {
                if (!$this->indexExists('applicants', 'idx_type_email')) {
                    $table->index(['registrantType', 'Email'], 'idx_type_email');
                }
                if (Schema::hasColumn('applicants', 'google_id') && !$this->indexExists('applicants', 'idx_google_id')) {
                    $table->index('google_id', 'idx_google_id');
                }
                if (Schema::hasColumn('applicants', 'last_login') && !$this->indexExists('applicants', 'idx_last_login')) {
                    $table->index('last_login', 'idx_last_login');
                }
            });
        }

        // Search logs indexes
        if (Schema::hasTable('search_logs')) {
            Schema::table('search_logs', function (Blueprint $table) {
                if (!$this->indexExists('search_logs', 'idx_user_search_date')) {
                    $table->index(['user_id', 'search_date'], 'idx_user_search_date');
                }
                if (!$this->indexExists('search_logs', 'idx_type_date')) {
                    $table->index(['search_type', 'search_date'], 'idx_type_date');
                }
            });
        }

        // Notifications indexes
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!$this->indexExists('notifications', 'idx_read_created')) {
                    $table->index(['is_read', 'created_at'], 'idx_read_created');
                }
                if (!$this->indexExists('notifications', 'idx_notification_type')) {
                    $table->index('type', 'idx_notification_type');
                }
            });
        }

        // Admin reports indexes
        if (Schema::hasTable('admin_reports')) {
            Schema::table('admin_reports', function (Blueprint $table) {
                if (!$this->indexExists('admin_reports', 'idx_admin_created')) {
                    $table->index(['admin_id', 'created_at'], 'idx_admin_created');
                }
                if (!$this->indexExists('admin_reports', 'idx_category_date')) {
                    $table->index(['category', 'report_date'], 'idx_category_date');
                }
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);

        return isset($indexes[$indexName]);
    }
};











