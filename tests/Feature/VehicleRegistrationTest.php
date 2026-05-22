<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VehicleRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(array $overrides = []): Applicant
    {
        return Applicant::create(array_merge([
            'registrantType' => 'student',
            'fullName'       => 'Jane Student',
            'email'          => 'jane@africau.edu',
            'password'       => Hash::make('SecurePass123!'),
            'phone'          => '0771234567',
            'college'        => 'Science',
            'idNumber'       => 'ID100',
            'studentRegNo'   => '200100',
            'licenseNumber'  => 'LIC100',
            'licenseClass'   => 'B',
            'licenseDate'    => '2021-01-01',
            'status'         => 'active',
        ], $overrides));
    }

    private function actAsStudent(Applicant $student): void
    {
        session([
            'user_id'    => $student->applicant_id,
            'user_type'  => $student->registrantType,
            'logged_in'  => true,
            'login_time' => time(),
        ]);
    }

    // ── Registration ─────────────────────────────────────────────────────────

    public function test_student_can_register_a_vehicle(): void
    {
        $student = $this->makeStudent();
        $this->actAsStudent($student);

        $response = $this->post('/register_vehicle.php', [
            'make'      => 'Toyota',
            'model'     => 'Corolla',
            'regNumber' => 'ABC-1234',
            'PlateNumber' => 'ABC 1234',
            'owner'     => 'Jane Student',
            'address'   => '123 Campus Rd',
        ]);

        $response->assertRedirect('/user-dashboard.php');
        $this->assertDatabaseHas('vehicles', [
            'applicant_id' => $student->applicant_id,
            'regNumber'    => 'ABC-1234',
            'status'       => 'active',
        ]);
    }

    public function test_vehicle_gets_expiry_date_on_registration(): void
    {
        $student = $this->makeStudent();
        $this->actAsStudent($student);

        $this->post('/register_vehicle.php', [
            'make'      => 'Honda',
            'model'     => 'Civic',
            'regNumber' => 'DEF-5678',
            'PlateNumber' => 'DEF 5678',
        ]);

        $vehicle = Vehicle::where('regNumber', 'DEF-5678')->first();
        $this->assertNotNull($vehicle->registration_expiry);
        $this->assertTrue($vehicle->registration_expiry->greaterThan(now()));
    }

    public function test_student_cannot_register_duplicate_registration_number(): void
    {
        $student = $this->makeStudent();
        $this->actAsStudent($student);

        $payload = [
            'make'      => 'Toyota',
            'regNumber' => 'DUP-9999',
            'PlateNumber' => 'DUP 9999',
        ];

        $this->post('/register_vehicle.php', $payload);

        $response = $this->post('/register_vehicle.php', $payload);
        $response->assertSessionHasErrors('regNumber');
    }

    public function test_registering_second_vehicle_deactivates_first_for_student(): void
    {
        $student = $this->makeStudent();
        $this->actAsStudent($student);

        $this->post('/register_vehicle.php', [
            'make' => 'Ford', 'regNumber' => 'CAR-001', 'PlateNumber' => 'CAR 001',
        ]);

        $this->post('/register_vehicle.php', [
            'make' => 'Mazda', 'regNumber' => 'CAR-002', 'PlateNumber' => 'CAR 002',
        ]);

        $first  = Vehicle::where('regNumber', 'CAR-001')->first();
        $second = Vehicle::where('regNumber', 'CAR-002')->first();

        $this->assertEquals('inactive', $first->status);
        $this->assertEquals('active',   $second->status);
    }

    public function test_unauthenticated_user_cannot_register_vehicle(): void
    {
        $response = $this->post('/register_vehicle.php', [
            'make'      => 'Toyota',
            'regNumber' => 'XYZ-111',
        ]);

        $response->assertRedirect('/login.php');
        $this->assertDatabaseMissing('vehicles', ['regNumber' => 'XYZ-111']);
    }

    // ── Renewal ───────────────────────────────────────────────────────────────

    public function test_student_can_renew_vehicle_registration(): void
    {
        $student = $this->makeStudent();
        $this->actAsStudent($student);

        $vehicle = Vehicle::create([
            'applicant_id'        => $student->applicant_id,
            'make'                => 'Nissan',
            'regNumber'           => 'RNW-001',
            'PlateNumber'         => 'RNW 001',
            'status'              => 'active',
            'registration_date'   => now()->subYear(),
            'registration_expiry' => now()->subDays(10)->toDateString(),
            'last_updated'        => now(),
        ]);

        $response = $this->postJson("/vehicles/{$vehicle->vehicle_id}/renew");

        $response->assertOk()->assertJsonFragment(['success' => true]);
        $vehicle->refresh();
        $this->assertTrue($vehicle->registration_expiry->isFuture());
    }

    // ── Deletion ─────────────────────────────────────────────────────────────

    public function test_student_cannot_delete_another_students_vehicle(): void
    {
        $student1 = $this->makeStudent(['email' => 's1@africau.edu', 'studentRegNo' => '111111']);
        $student2 = $this->makeStudent(['email' => 's2@africau.edu', 'studentRegNo' => '222222']);

        $vehicle = Vehicle::create([
            'applicant_id'      => $student1->applicant_id,
            'make'              => 'BMW',
            'regNumber'         => 'OWN-001',
            'PlateNumber'       => 'OWN 001',
            'status'            => 'active',
            'registration_date' => now(),
            'last_updated'      => now(),
        ]);

        // Student2 tries to delete student1's vehicle
        session([
            'user_id'    => $student2->applicant_id,
            'logged_in'  => true,
            'login_time' => time(),
        ]);

        $response = $this->post('/delete_vehicle.php', ['id' => $vehicle->vehicle_id]);
        $response->assertForbidden();
        $this->assertDatabaseHas('vehicles', ['vehicle_id' => $vehicle->vehicle_id]);
    }
}
