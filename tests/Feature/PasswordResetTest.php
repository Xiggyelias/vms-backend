<?php

namespace Tests\Feature;

use App\Models\Applicant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function makeStudent(array $overrides = []): Applicant
    {
        return Applicant::create(array_merge([
            'registrantType' => 'student',
            'fullName'       => 'Reset Student',
            'email'          => 'reset@africau.edu',
            'password'       => Hash::make('OldPassword123!'),
            'phone'          => '0770000001',
            'college'        => 'Arts',
            'idNumber'       => 'ID300',
            'studentRegNo'   => '400300',
            'licenseNumber'  => 'LIC300',
            'licenseClass'   => 'B',
            'licenseDate'    => '2023-03-01',
            'status'         => 'active',
        ], $overrides));
    }

    private function insertToken(Applicant $user, array $overrides = []): string
    {
        $token = Str::random(64);

        DB::table('password_reset_tokens')->insert(array_merge([
            'user_id'    => $user->applicant_id,
            'user_type'  => 'applicant',
            'token'      => hash('sha256', $token),
            'created_at' => now(),
            'expires_at' => now()->addHour(),
            'used'       => false,
        ], $overrides));

        return $token;
    }

    // ── Request reset link ────────────────────────────────────────────────────

    public function test_reset_link_request_shows_generic_message_for_unknown_email(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password.php', [
            'email' => 'nobody@africau.edu',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Mail::assertNothingSent();
    }

    public function test_reset_link_is_sent_for_known_email(): void
    {
        Mail::fake();
        $user = $this->makeStudent();

        $response = $this->post('/forgot-password.php', [
            'email' => 'reset@africau.edu',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_tokens', [
            'user_id'   => $user->applicant_id,
            'user_type' => 'applicant',
            'used'      => false,
        ]);
    }

    public function test_previous_token_is_invalidated_on_new_request(): void
    {
        Mail::fake();
        $user  = $this->makeStudent();
        $this->insertToken($user);

        $this->post('/forgot-password.php', ['email' => 'reset@africau.edu']);

        // The old token should now be marked used
        $oldToken = DB::table('password_reset_tokens')
            ->where('user_id', $user->applicant_id)
            ->orderBy('created_at')
            ->first();

        $this->assertTrue((bool) $oldToken->used);
    }

    // ── Use valid token ───────────────────────────────────────────────────────

    public function test_password_is_reset_with_valid_token(): void
    {
        $user  = $this->makeStudent();
        $token = $this->insertToken($user);

        $response = $this->post('/reset-password.php', [
            'email'                 => 'reset@africau.edu',
            'token'                 => $token,
            'password'              => 'NewSecurePass456!',
            'password_confirmation' => 'NewSecurePass456!',
        ]);

        $response->assertRedirect('/login.php');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePass456!', $user->password));
    }

    public function test_token_is_marked_used_after_successful_reset(): void
    {
        $user  = $this->makeStudent();
        $token = $this->insertToken($user);

        $this->post('/reset-password.php', [
            'email'                 => 'reset@africau.edu',
            'token'                 => $token,
            'password'              => 'NewSecurePass456!',
            'password_confirmation' => 'NewSecurePass456!',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('user_id', $user->applicant_id)
            ->first();

        $this->assertTrue((bool) $record->used);
    }

    // ── Expired token ─────────────────────────────────────────────────────────

    public function test_expired_token_cannot_reset_password(): void
    {
        $user  = $this->makeStudent();
        $token = $this->insertToken($user, [
            'expires_at' => now()->subMinutes(5),
        ]);

        $response = $this->post('/reset-password.php', [
            'email'                 => 'reset@africau.edu',
            'token'                 => $token,
            'password'              => 'NewSecurePass456!',
            'password_confirmation' => 'NewSecurePass456!',
        ]);

        $response->assertSessionHasErrors('token');

        // Password must remain unchanged
        $user->refresh();
        $this->assertTrue(Hash::check('OldPassword123!', $user->password));
    }

    // ── Already-used token ────────────────────────────────────────────────────

    public function test_used_token_cannot_reset_password_again(): void
    {
        $user  = $this->makeStudent();
        $token = $this->insertToken($user, ['used' => true]);

        $response = $this->post('/reset-password.php', [
            'email'                 => 'reset@africau.edu',
            'token'                 => $token,
            'password'              => 'AnotherPass789!',
            'password_confirmation' => 'AnotherPass789!',
        ]);

        $response->assertSessionHasErrors('token');
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_reset_requires_minimum_12_char_password(): void
    {
        $user  = $this->makeStudent();
        $token = $this->insertToken($user);

        $response = $this->post('/reset-password.php', [
            'email'                 => 'reset@africau.edu',
            'token'                 => $token,
            'password'              => 'Short1!',
            'password_confirmation' => 'Short1!',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_reset_requires_password_confirmation_to_match(): void
    {
        $user  = $this->makeStudent();
        $token = $this->insertToken($user);

        $response = $this->post('/reset-password.php', [
            'email'                 => 'reset@africau.edu',
            'token'                 => $token,
            'password'              => 'NewSecurePass456!',
            'password_confirmation' => 'DifferentPass456!',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
