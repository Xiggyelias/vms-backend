<?php

namespace Tests\Feature;

use App\Models\Applicant;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VehicleSearchTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(array $overrides = []): Applicant
    {
        return Applicant::create(array_merge([
            'registrantType' => 'student',
            'fullName'       => 'Search Student',
            'email'          => 'searcher@africau.edu',
            'password'       => Hash::make('SecurePass123!'),
            'phone'          => '0779876543',
            'college'        => 'Engineering',
            'idNumber'       => 'ID200',
            'studentRegNo'   => '300200',
            'licenseNumber'  => 'LIC200',
            'licenseClass'   => 'B',
            'licenseDate'    => '2022-06-01',
            'status'         => 'active',
        ], $overrides));
    }

    private function makeVehicle(Applicant $student, array $overrides = []): Vehicle
    {
        return Vehicle::create(array_merge([
            'applicant_id'        => $student->applicant_id,
            'make'                => 'Toyota',
            'model'               => 'Hilux',
            'regNumber'           => 'TST-0001',
            'PlateNumber'         => 'TST 0001',
            'status'              => 'active',
            'registration_date'   => now(),
            'registration_expiry' => now()->addYear()->toDateString(),
            'last_updated'        => now(),
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

    // ── Found / Not found ─────────────────────────────────────────────────────

    public function test_search_returns_vehicle_when_plate_exists(): void
    {
        $student = $this->makeStudent();
        $this->actAsStudent($student);
        $vehicle = $this->makeVehicle($student);

        $response = $this->postJson('/search-vehicle.php', [
            'plateNumber' => 'TST 0001',
        ]);

        $response->assertOk()
                 ->assertJsonFragment(['isRegistered' => true])
                 ->assertJsonPath('data.PlateNumber', 'TST 0001');
    }

    public function test_search_returns_not_registered_when_plate_missing(): void
    {
        $student = $this->makeStudent();
        $this->actAsStudent($student);

        $response = $this->postJson('/search-vehicle.php', [
            'plateNumber' => 'GHOST 999',
        ]);

        $response->assertOk()
                 ->assertJsonFragment(['isRegistered' => false]);
    }

    public function test_search_accepts_query_alias_parameter(): void
    {
        $student = $this->makeStudent();
        $this->actAsStudent($student);
        $vehicle = $this->makeVehicle($student);

        $response = $this->postJson('/search-vehicle.php', [
            'query' => 'TST 0001',
        ]);

        $response->assertOk()->assertJsonFragment(['isRegistered' => true]);
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_search_returns_422_when_no_plate_provided(): void
    {
        $student = $this->makeStudent();
        $this->actAsStudent($student);

        $response = $this->postJson('/search-vehicle.php', []);

        $response->assertStatus(422)
                 ->assertJsonFragment(['isRegistered' => false]);
    }

    // ── Auth guard ────────────────────────────────────────────────────────────

    public function test_unauthenticated_search_returns_401(): void
    {
        $response = $this->postJson('/search-vehicle.php', [
            'plateNumber' => 'ANY 0001',
        ]);

        $response->assertStatus(401);
    }

    // ── Cache behaviour ───────────────────────────────────────────────────────

    public function test_repeated_search_for_same_plate_hits_cache(): void
    {
        $student = $this->makeStudent();
        $this->actAsStudent($student);
        $vehicle = $this->makeVehicle($student);

        // Prime the cache with first call
        $this->postJson('/search-vehicle.php', ['plateNumber' => 'TST 0001']);

        // Delete the vehicle from the DB; cache should still return it
        $vehicle->delete();

        $response = $this->postJson('/search-vehicle.php', ['plateNumber' => 'TST 0001']);

        $response->assertOk()->assertJsonFragment(['isRegistered' => true]);
    }

    public function test_cache_is_bypassed_when_cleared(): void
    {
        $student = $this->makeStudent();
        $this->actAsStudent($student);
        $vehicle = $this->makeVehicle($student);

        // Prime the cache
        $this->postJson('/search-vehicle.php', ['plateNumber' => 'TST 0001']);

        // Delete vehicle and clear cache
        $vehicle->delete();
        Cache::forget('vehicle_search_plate:TST 0001');

        $response = $this->postJson('/search-vehicle.php', ['plateNumber' => 'TST 0001']);

        $response->assertOk()->assertJsonFragment(['isRegistered' => false]);
    }
}
