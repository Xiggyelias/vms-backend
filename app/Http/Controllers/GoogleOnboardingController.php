<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleOnboardingController extends Controller
{
    public function googleAuth(Request $request)
    {
        $idToken = (string) $request->input('credential', '');
        if ($idToken === '') {
            return $this->fail('Missing token', 400);
        }

        $verify = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (!$verify->ok()) {
            return $this->fail('Token verification failed', 401);
        }

        $tokenInfo = $verify->json();
        if (($tokenInfo['aud'] ?? '') !== (string) config('services.google.client_id')) {
            return $this->fail('Invalid audience', 401);
        }

        $email = (string) ($tokenInfo['email'] ?? '');
        $isVerified = (string) ($tokenInfo['email_verified'] ?? 'false') === 'true';
        if ($email === '' || !$isVerified) {
            return $this->fail('Email not verified', 401);
        }

        $allowedDomain = strtolower((string) config('app.allowed_google_domain', 'africau.edu'));
        $emailDomain = strtolower((string) substr(strrchr($email, '@') ?: '', 1));
        if ($emailDomain !== $allowedDomain) {
            return $this->fail('Only Africa University emails are allowed.', 403);
        }

        $fullName = (string) ($tokenInfo['name'] ?? '');
        $user = Applicant::where('email', $email)->first();

        if (!$user) {
            $user = Applicant::create([
                'fullName' => $fullName ?: 'Unknown User',
                'email' => $email,
                'registrantType' => 'pending',
                'password' => Hash::make(Str::random(32)),
                'phone' => '',
                'college' => '',
                'idNumber' => '',
                'licenseNumber' => '',
                'licenseClass' => '',
                'licenseDate' => now(),
            ]);
        }

        $type = strtolower((string) ($user->registrantType ?? ''));
        $hasStudentId = preg_match('/^\d{6}$/', (string) ($user->studentRegNo ?? '')) === 1;
        $hasStaffId = preg_match('/^[A-Za-z0-9]{5}$/', (string) ($user->staffsRegNo ?? '')) === 1;
        $needsSetup = !in_array($type, ['student', 'staff'], true)
            || ($type === 'student' && !$hasStudentId)
            || ($type === 'staff' && !$hasStaffId);

        if ($needsSetup) {
            session(['pending_oauth.' . $user->applicant_id => [
                'email' => $email,
                'name' => $fullName,
            ]]);

            return $this->ok([
                'requires_type_selection' => true,
                'temp_user_id' => (int) $user->applicant_id,
                'user_info' => [
                    'id' => (int) $user->applicant_id,
                    'name' => $fullName,
                    'email' => $email,
                ],
            ], 'Role setup required.');
        }

        session()->regenerate();
        session([
            'user_id' => $user->applicant_id,
            'user_email' => $email,
            'user_name' => $user->fullName,
            'user_type' => $type,
            'logged_in' => true,
            'login_time' => time(),
        ]);

        return $this->ok([
            'redirect' => 'user-dashboard.php',
            'user_info' => [
                'id' => (int) $user->applicant_id,
                'name' => $user->fullName,
                'email' => $email,
                'registrant_type' => $type,
            ],
        ], 'Login successful.');
    }

    public function finalizeRole(Request $request)
    {
        $tempUserId = (int) ($request->input('temp_user_id') ?: $request->input('user_id'));
        $type = strtolower(trim((string) ($request->input('registrantType') ?: $request->input('registrant_type'))));
        $identity = trim((string) ($request->input('identity') ?: $request->input('identifier')));

        if ($tempUserId <= 0 || !in_array($type, ['student', 'staff'], true)) {
            return $this->fail('Invalid parameters', 400);
        }

        $pending = session('pending_oauth.' . $tempUserId);
        if (!$pending) {
            return $this->fail('Session expired. Please sign in again.', 400);
        }

        if ($type === 'student' && !preg_match('/^\d{6}$/', $identity)) {
            return $this->fail('Failed. Please provide a valid identifier for the selected role.', 400);
        }
        if ($type === 'staff' && !preg_match('/^[A-Za-z0-9]{5}$/', $identity)) {
            return $this->fail('Failed. Please provide a valid identifier for the selected role.', 400);
        }

        $user = Applicant::find($tempUserId);
        if (!$user) {
            return $this->fail('User not found.', 404);
        }

        if ($type === 'student') {
            $user->registrantType = 'student';
            $user->studentRegNo = $identity;
            $user->staffsRegNo = null;
        } else {
            $user->registrantType = 'staff';
            $user->staffsRegNo = $identity;
            $user->studentRegNo = null;
        }
        $user->last_login = now();
        $user->save();

        session()->regenerate();
        session([
            'user_id' => $user->applicant_id,
            'user_email' => $user->email,
            'user_name' => $user->fullName,
            'user_type' => $user->registrantType,
            'logged_in' => true,
            'login_time' => time(),
        ]);
        session()->forget('pending_oauth.' . $tempUserId);

        return $this->ok([
            'status' => 'success',
            'role' => $user->registrantType,
            'redirect' => 'user-dashboard.php',
            'user' => [
                'id' => (int) $user->applicant_id,
                'email' => $user->email,
                'name' => $user->fullName,
            ],
        ], 'Role finalized.');
    }
}

