<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->seedAdmins();
        $this->seedSampleData();
        $this->seedNotifications();
    }

    private function seedAdmins(): void
    {
        $adminPassword = env('SEED_ADMIN_PASSWORD');

        // Only create admin if none exists
        if (DB::table('admins')->count() === 0 && !empty($adminPassword)) {
            DB::table('admins')->insert([
                'username' => env('SEED_ADMIN_USERNAME', 'admin'),
                'password' => Hash::make($adminPassword),
                'email' => env('SEED_ADMIN_EMAIL', 'admin@au.ac.zw'),
                'created_at' => now(),
            ]);
        }
    }

    private function seedSampleData(): void
    {
        $allowSampleSeeding = app()->environment('local') || filter_var(env('ALLOW_SAMPLE_SEEDING', false), FILTER_VALIDATE_BOOL);
        if (!$allowSampleSeeding) {
            return;
        }

        // Only add sample data if tables are empty
        if (DB::table('applicants')->count() === 0) {
            // Sample applicants
            $applicants = [
                [
                    'registrantType' => 'student',
                    'studentRegNo' => '230518',
                    'fullName' => 'John Doe',
                    'password' => Hash::make('password123'),
                    'phone' => '+263 77 123 4567',
                    'email' => 'john.doe@student.au.ac.zw',
                    'college' => 'College of Engineering and Applied Sciences',
                    'idNumber' => 'R000855531',
                    'licenseNumber' => 'CD123456',
                    'licenseClass' => 'C',
                    'licenseDate' => '2020-02-22',
                    'status' => 'active',
                ],
                [
                    'registrantType' => 'staff',
                    'staffsRegNo' => 'STF-001',
                    'fullName' => 'Jane Smith',
                    'password' => Hash::make('password123'),
                    'phone' => '+263 77 987 6543',
                    'email' => 'jane.smith@au.ac.zw',
                    'college' => 'College of Social Sciences',
                    'idNumber' => 'ZW54321098',
                    'licenseNumber' => 'DL-ZIM-220198',
                    'licenseClass' => 'B',
                    'licenseDate' => '2022-03-28',
                    'status' => 'active',
                ],
            ];

            foreach ($applicants as $applicant) {
                DB::table('applicants')->insert($applicant);
            }
        }

        // Sample vehicles
        if (DB::table('vehicles')->count() === 0) {
            $vehicles = [
                [
                    'applicant_id' => 1,
                    'regNumber' => 'AU-REG-001',
                    'make' => 'Toyota',
                    'model' => 'Corolla',
                    'owner' => 'John Doe',
                    'address' => '123 University Avenue, Mutare',
                    'PlateNumber' => 'ZIM-1234',
                    'registration_date' => now(),
                    'status' => 'active',
                ],
                [
                    'applicant_id' => 2,
                    'regNumber' => 'AU-REG-002',
                    'make' => 'Honda',
                    'model' => 'Civic',
                    'owner' => 'Jane Smith',
                    'address' => '456 Academic Lane, Mutare',
                    'PlateNumber' => 'ZIM-5678',
                    'registration_date' => now(),
                    'status' => 'active',
                ],
            ];

            foreach ($vehicles as $vehicle) {
                DB::table('vehicles')->insert($vehicle);
            }
        }

        // Sample authorized drivers
        if (DB::table('authorized_driver')->count() === 0) {
            $drivers = [
                [
                    'vehicle_id' => 1,
                    'fullname' => 'Alice Johnson',
                    'licenseNumber' => 'DL-ZIM-330299',
                    'contact' => '+263 71 234 5678',
                    'applicant_id' => 1,
                ],
                [
                    'vehicle_id' => 2,
                    'fullname' => 'Bob Wilson',
                    'licenseNumber' => 'DL-ZIM-440388',
                    'contact' => '+263 73 345 6789',
                    'applicant_id' => 2,
                ],
            ];

            foreach ($drivers as $driver) {
                DB::table('authorized_driver')->insert($driver);
            }
        }
    }

    private function seedNotifications(): void
    {
        if (DB::table('notifications')->count() === 0) {
            $notifications = [
                [
                    'title' => 'Welcome to AU Vehicle Registration System',
                    'message' => 'Your account has been successfully created. Please complete your vehicle registration.',
                    'is_read' => false,
                    'type' => 'welcome',
                    'created_at' => now(),
                ],
                [
                    'title' => 'Vehicle Registration Reminder',
                    'message' => 'Don\'t forget to register your vehicle to access campus parking facilities.',
                    'is_read' => false,
                    'type' => 'reminder',
                    'created_at' => now()->subDays(1),
                ],
                [
                    'title' => 'License Renewal Notice',
                    'message' => 'Your driver\'s license renewal is due within the next 30 days.',
                    'is_read' => false,
                    'type' => 'renewal',
                    'created_at' => now()->subDays(2),
                ],
            ];

            foreach ($notifications as $notification) {
                DB::table('notifications')->insert($notification);
            }
        }
    }
}
