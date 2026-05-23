<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GoogleOnboardingController extends Controller
{
    public function googleAuth(Request $request)
    {
        try {
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
            $columns = Schema::hasTable('applicants') ? Schema::getColumnListing('applicants') : [];
            $emailCol = $this->resolveColumn($columns, 'email');
            $nameCol = $this->resolveColumn($columns, 'fullName') ?? $this->resolveColumn($columns, 'fullname');

            if ($emailCol === null) {
                return $this->fail('Applicant email column is missing.', 500);
            }

            $userRow = DB::table('applicants')->where($emailCol, $email)->first();
            $user = null;

            if ($userRow && isset($userRow->applicant_id)) {
                $user = Applicant::find((int) $userRow->applicant_id);
            }

            if (!$user) {
                $insert = $this->buildApplicantInsertPayload($columns, $emailCol, $nameCol, $email, $fullName);
                $newId = DB::table('applicants')->insertGetId($insert);
                $user = Applicant::find((int) $newId);
            }

            if (!$user) {
                return $this->fail('Unable to create or load applicant account.', 500);
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
                'user_id'      => $user->applicant_id,
                'user_email'   => $email,
                'user_name'    => $user->fullName,
                'user_type'    => $type,
                'user_college' => (string) ($user->college ?? ''),
                'logged_in'    => true,
                'login_time'   => time(),
            ]);

            $authToken = $this->buildAuthToken([
                'uid'     => (int) $user->applicant_id,
                'email'   => $email,
                'name'    => $user->fullName,
                'type'    => $type,
                'college' => (string) ($user->college ?? ''),
            ]);

            return $this->ok([
                'redirect'   => 'user-dashboard.php',
                'auth_token' => $authToken,
                'user_info'  => [
                    'id'              => (int) $user->applicant_id,
                    'name'            => $user->fullName,
                    'email'           => $email,
                    'registrant_type' => $type,
                ],
            ], 'Login successful.');
        } catch (\Throwable $e) {
            Log::error('google_auth.php failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return $this->fail('Google sign-in failed. Please try again.', 500);
        }
    }

    private function resolveColumn(array $columns, string $wanted): ?string
    {
        foreach ($columns as $column) {
            if (strcasecmp($column, $wanted) === 0) {
                return $column;
            }
        }
        return null;
    }

    private function buildApplicantInsertPayload(
        array $columns,
        string $emailCol,
        ?string $nameCol,
        string $email,
        string $fullName
    ): array {
        $data = [];

        $data[$emailCol] = $email;
        if ($nameCol !== null) {
            $data[$nameCol] = $fullName !== '' ? $fullName : 'Unknown User';
        }

        $map = [
            'registrantType' => 'guest',
            'password' => Hash::make(Str::random(32)),
            'phone' => '',
            'college' => '',
            'idNumber' => '',
            'licenseNumber' => '',
            'licenseClass' => '',
            'licenseDate' => now()->toDateString(),
        ];

        foreach ($map as $columnName => $value) {
            $col = $this->resolveColumn($columns, $columnName);
            if ($col !== null) {
                $data[$col] = $value;
            }
        }

        return $data;
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
            'user_id'      => $user->applicant_id,
            'user_email'   => $user->email,
            'user_name'    => $user->fullName,
            'user_type'    => $user->registrantType,
            'user_college' => (string) ($user->college ?? ''),
            'logged_in'    => true,
            'login_time'   => time(),
        ]);
        session()->forget('pending_oauth.' . $tempUserId);

        $authToken = $this->buildAuthToken([
            'uid'     => (int) $user->applicant_id,
            'email'   => $user->email,
            'name'    => $user->fullName,
            'type'    => $user->registrantType,
            'college' => (string) ($user->college ?? ''),
        ]);

        return $this->ok([
            'status'     => 'success',
            'role'       => $user->registrantType,
            'redirect'   => 'user-dashboard.php',
            'auth_token' => $authToken,
            'user'       => [
                'id'    => (int) $user->applicant_id,
                'email' => $user->email,
                'name'  => $user->fullName,
            ],
        ], 'Role finalized.');
    }

    /**
     * Establish a Laravel session from a short-lived auth token.
     * Called by auth-establish.php via the same-origin proxy so the session
     * cookie is scoped to the frontend domain (vehicle.africau.co.zw).
     */
    public function authSync(Request $request): JsonResponse
    {
        $token = (string) $request->input('token', '');
        if ($token === '') {
            return $this->fail('Missing token', 400);
        }

        $claims = $this->verifyAuthToken($token);
        if ($claims === null) {
            return $this->fail('Invalid or expired token', 401);
        }

        $uid = (int) ($claims['uid'] ?? 0);
        if ($uid <= 0) {
            return $this->fail('Invalid token claims', 401);
        }

        session()->regenerate();
        session([
            'user_id'      => $uid,
            'user_email'   => $claims['email'] ?? '',
            'user_name'    => $claims['name'] ?? '',
            'user_type'    => $claims['type'] ?? '',
            'user_college' => $claims['college'] ?? '',
            'logged_in'    => true,
            'login_time'   => time(),
        ]);

        return $this->ok([], 'Session established.');
    }
}
