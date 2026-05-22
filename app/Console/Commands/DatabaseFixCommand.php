<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseFixCommand extends Command
{
    protected $signature   = 'db:fix {--backup : Create database backup before fixes}';
    protected $description = 'Fix database inconsistencies and apply all improvements';

    public function handle(): int
    {
        $this->info('Starting database fix process...');

        if ($this->option('backup')) {
            $this->createBackup();
        }

        $this->fixAdminTables();
        $this->fixColumnTypes();
        $this->fixDataIntegrity();
        $this->addMissingIndexes();
        $this->verifyFixes();

        $this->info('Database fixes completed successfully!');
        $this->info('Run: php artisan migrate:fresh --seed');

        return Command::SUCCESS;
    }

    private function createBackup(): void
    {
        $this->info('Creating database backup...');

        $backupDir  = storage_path('backups');
        $backupFile = $backupDir . '/db_backup_' . date('Y_m_d_H_i_s') . '.sql';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0750, true);
        }

        // Write a temporary defaults file so the password is never visible
        // in the process list or shell history.
        $tmpCnf = tempnam(sys_get_temp_dir(), 'mysqldump_');
        try {
            file_put_contents($tmpCnf, implode("\n", [
                '[mysqldump]',
                'user='     . config('database.connections.mysql.username'),
                'password=' . config('database.connections.mysql.password'),
                'host='     . config('database.connections.mysql.host', '127.0.0.1'),
                'port='     . config('database.connections.mysql.port', 3306),
            ]));
            chmod($tmpCnf, 0600);

            $cmd = sprintf(
                'mysqldump --defaults-extra-file=%s %s > %s 2>&1',
                escapeshellarg($tmpCnf),
                escapeshellarg(config('database.connections.mysql.database')),
                escapeshellarg($backupFile)
            );

            exec($cmd, $output, $exitCode);

            if ($exitCode === 0) {
                $this->info("Backup created: {$backupFile}");
            } else {
                $this->warn('Backup failed. Continuing with fixes...');
                $this->warn(implode("\n", $output));
            }
        } finally {
            // Always remove the temp credentials file
            if (file_exists($tmpCnf)) {
                unlink($tmpCnf);
            }
        }
    }

    private function fixAdminTables(): void
    {
        $this->info('Fixing admin table inconsistencies...');

        if (Schema::hasTable('admin') && Schema::hasTable('admins')) {
            if (DB::table('admins')->count() === 0) {
                DB::statement('INSERT INTO admins (username, password, email, created_at) SELECT username, password, email, created_at FROM admin');
                $this->info('Migrated admin data from admin to admins table');
            }

            Schema::dropIfExists('admin');
            $this->info('Dropped duplicate admin table');
        }
    }

    private function fixColumnTypes(): void
    {
        $this->info('Fixing column types...');

        if (Schema::hasTable('authorized_driver') && DB::connection()->getDriverName() !== 'sqlite') {
            // Safe: table and column are literals, not user input
            $rows = DB::select(
                'SHOW COLUMNS FROM `authorized_driver` WHERE Field = ?',
                ['licenseNumber']
            );
            $currentType = $rows[0]->Type ?? '';

            if (str_contains(strtolower($currentType), 'int')) {
                DB::statement('ALTER TABLE authorized_driver MODIFY COLUMN licenseNumber VARCHAR(50) NOT NULL');
                $this->info('Fixed licenseNumber column type from INT to VARCHAR');
            }
        }

        $this->addMissingColumns();
    }

    private function addMissingColumns(): void
    {
        if (Schema::hasTable('applicants') && !Schema::hasColumn('applicants', 'status')) {
            Schema::table('applicants', function ($t) {
                $t->string('status')->default('active')->after('licenseDate');
            });
            $this->info('Added status column to applicants table');
        }

        if (Schema::hasTable('vehicles') && !Schema::hasColumn('vehicles', 'model')) {
            Schema::table('vehicles', function ($t) {
                $t->string('model', 255)->nullable()->after('make');
            });
            $this->info('Added model column to vehicles table');
        }

        if (Schema::hasTable('applicants')) {
            foreach (['google_id' => 'email', 'avatar' => 'google_id', 'last_login' => 'status'] as $col => $after) {
                if (!Schema::hasColumn('applicants', $col)) {
                    Schema::table('applicants', function ($t) use ($col, $after) {
                        if ($col === 'last_login') {
                            $t->timestamp($col)->nullable()->after($after);
                        } else {
                            $t->string($col, 255)->nullable()->after($after);
                        }
                    });
                    $this->info("Added {$col} column to applicants table");
                }
            }
        }
    }

    private function fixDataIntegrity(): void
    {
        $this->info('Fixing data integrity issues...');

        foreach (['0003-02-22', '5465-02-23', '2222-01-22'] as $date) {
            DB::table('applicants')
                ->where('licenseDate', $date)
                ->update(['licenseDate' => now()->subYears(5)->format('Y-m-d')]);
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::table('applicants')->where('email', '')
                ->update(['email' => DB::raw("'user_' || applicant_id || '@example.com'")]);

            DB::table('authorized_driver')
                ->where('licenseNumber', 0)->orWhere('licenseNumber', 2147483647)
                ->update(['licenseNumber' => DB::raw("'TEMP_' || Id")]);
        } else {
            DB::table('applicants')->where('email', '')
                ->update(['email' => DB::raw("CONCAT('user_', applicant_id, '@example.com')")]);

            DB::table('authorized_driver')
                ->where('licenseNumber', 0)->orWhere('licenseNumber', 2147483647)
                ->update(['licenseNumber' => DB::raw("CONCAT('TEMP_', Id)")]);
        }

        DB::table('authorized_driver')->where('vehicle_id', 0)->update(['vehicle_id' => null]);

        $this->info('Data integrity issues fixed');
    }

    private function addMissingIndexes(): void
    {
        $this->info('Adding performance indexes...');

        $indexes = [
            'vehicles' => [
                [['applicant_id', 'status'], 'idx_applicant_status'],
                [['regNumber', 'status'],    'idx_regnumber_status'],
                [['PlateNumber'],            'idx_plate_number'],
                [['registration_date'],      'idx_registration_date'],
            ],
            'authorized_driver' => [
                [['applicant_id', 'vehicle_id'], 'idx_applicant_vehicle'],
                [['licenseNumber'],              'idx_license_number'],
            ],
            'applicants' => [
                [['registrantType', 'email'], 'idx_type_email'],
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            Schema::table($table, function ($schema) use ($table, $tableIndexes) {
                foreach ($tableIndexes as [$columns, $name]) {
                    if (!$this->indexExists($table, $name)) {
                        $schema->index($columns, $name);
                    }
                }
            });
        }

        $this->info('Performance indexes added');
    }

    private function verifyFixes(): void
    {
        $this->info('Verifying database fixes...');

        $checks = [
            'Admin table unified'          => !Schema::hasTable('admin') && Schema::hasTable('admins'),
            'Applicants have status column' => Schema::hasColumn('applicants', 'status'),
            'Vehicles have model column'   => Schema::hasColumn('vehicles', 'model'),
            'Google auth columns exist'    => Schema::hasColumn('applicants', 'google_id'),
        ];

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $checks['licenseNumber is VARCHAR'] = $this->checkColumnType('authorized_driver', 'licenseNumber', 'varchar');
        }

        foreach ($checks as $description => $result) {
            $result
                ? $this->info("  OK: {$description}")
                : $this->warn("  FAIL: {$description}");
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $indexes = Schema::getConnection()
                ->getDoctrineSchemaManager()
                ->listTableIndexes($table);
            return isset($indexes[$indexName]);
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkColumnType(string $table, string $column, string $expected): bool
    {
        try {
            // Table name is a literal constant from internal code — sanitise the
            // backtick-wrapped identifier; column is safely parameterised.
            $safeName = '`' . str_replace('`', '', $table) . '`';
            $rows = DB::select("SHOW COLUMNS FROM {$safeName} WHERE Field = ?", [$column]);
            return isset($rows[0]) && str_contains(strtolower($rows[0]->Type), $expected);
        } catch (\Throwable) {
            return false;
        }
    }
}
