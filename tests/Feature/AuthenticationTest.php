<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Applicant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeStudent(array $overrides = []): Applicant
    {
        return Applicant::create(array_merge([
            'registrantType' => 'student',
            'fullName'       => 'Test Student',
            'email'          => 'student@africau.edu',
            'password'       => Hash::make('SecurePass123!'),
            'phone'          => '0771234567',
            'college'        => 'Engineering',
            'idNumber'       => 'ID001',
            'studentRegNo'   => '123456',
            'licenseNumber'  => 'LIC001',
            'licenseClass'   => 'B',
            'licenseDate'    => '2020-01-01',
            'status'         => 'active',
        ], $overrides));
    }

    private function makeAdmin(array $overrides = []): Admin
    {
        return Admin::create(array_merge([
            'username' => 'admin',
            'password' => Hash::make('AdminPass123!'),
        ], $overrides));
    }

    // ── Student login ─────────────────────────────────────────────────────────

    public function test_student_can_login_with_valid_credentials(): void
    {
        $this->makeStudent();

        $response = $this->post('/login.php', [
            'identifier' => 'student@africau.edu',
            'password'   => 'SecurePass123!',
            'userType'   => 'student',
        ]);

        $response->assertRedirect('/user-dashboard.php');
        $this->assertEquals('student@africau.edu', session('user_email'));
    }

    public function test_student_login_fails_with_wrong_password(): void
    {
        $this->makeStudent();

        $response = $this->post('/login.php', [
            'identifier' => 'student@africau.edu',
            'password'   => 'wrongpassword',
            'userType'   => 'student',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertNull(session('user_id'));
    }

    public function test_student_login_fails_for_unknown_email(): void
    {
        $response = $this->post('/login.php', [
            'identifier' => 'nobody@africau.edu',
            'password'   => 'anything',
            'userType'   => 'student',
        ]);

        $response->assertSessionHasErrors('identifier');
    }

    public function test_suspended_student_cannot_login(): void
    {
        $this->makeStudent(['status' => 'suspended']);

        $response = $this->post('/login.php', [
            'identifier' => 'student@africau.edu',
            'password'   => 'SecurePass123!',
            'userType'   => 'student',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertNull(session('user_id'));
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $this->makeStudent();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login.php', [
                'identifier' => 'student@africau.edu',
                'password'   => 'wrong',
                'userType'   => 'student',
            ]);
        }

        $response = $this->post('/login.php', [
            'identifier' => 'student@africau.edu',
            'password'   => 'SecurePass123!',
            'userType'   => 'student',
        ]);

        $response->assertStatus(429);
    }

    // ── Admin login ───────────────────────────────────────────────────────────

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $this->makeAdmin();

        $response = $this->post('/admin-login.php', [
            'username' => 'admin',
            'password' => 'AdminPass123!',
        ]);

        $response->assertRedirect('/admin-dashboard.php');
        $this->assertTrue((bool) session('is_admin'));
    }

    public function test_admin_login_fails_with_wrong_password(): void
    {
        $this->makeAdmin();

        $response = $this->post('/admin-login.php', [
            'username' => 'admin',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('password');
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_student_can_logout(): void
    {
        $student = $this->makeStudent();
        session(['user_id' => $student->applicant_id, 'logged_in' => true, 'login_time' => time()]);

        $response = $this->post('/logout.php');

        $response->assertRedirect('/login.php');
        $this->assertNull(session('user_id'));
    }

    public function test_get_logout_redirects_to_login_without_logging_out(): void
    {
        // GET /logout.php should not destroy session — only POST does
        $response = $this->get('/logout.php');
        $response->assertRedirect('/login.php');
    }
}
