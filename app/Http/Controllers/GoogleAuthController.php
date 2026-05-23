<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = Applicant::where('google_id', $googleUser->getId())
                ->orWhere('email', $googleUser->getEmail())
                ->first();

            if ($user) {
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                        'avatar'    => $googleUser->getAvatar(),
                    ]);
                }
            } else {
                $emailDomain = substr(strrchr($googleUser->getEmail(), '@'), 1);
                $allowed     = config('app.allowed_google_domain', 'africau.edu');

                if ($emailDomain !== $allowed) {
                    $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
                    return redirect($frontendUrl . '/login.php?error=domain_not_allowed');
                }

                $user = Applicant::create([
                    'registrantType' => 'student',
                    'fullName'       => $googleUser->getName(),
                    'email'          => $googleUser->getEmail(),
                    'google_id'      => $googleUser->getId(),
                    'avatar'         => $googleUser->getAvatar(),
                    'password'       => bcrypt(Str::random(32)),
                    'phone'          => '',
                    'college'        => '',
                    'idNumber'       => '',
                    'licenseNumber'  => '',
                    'licenseClass'   => '',
                    'licenseDate'    => now(),
                    'studentRegNo'   => $this->generateUniqueStudentRegNo(),
                ]);
            }

            $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

            if (!in_array($user->registrantType, ['student', 'staff'], true)) {
                return redirect($frontendUrl . '/login.php?error=invalid_role');
            }

            if (($user->status ?? 'active') === 'suspended') {
                return redirect($frontendUrl . '/login.php?error=suspended');
            }

            // Build a short-lived HMAC token so auth-establish.php on the frontend
            // can set the PHP session without sharing a session store with Laravel.
            $authToken = $this->buildAuthToken([
                'uid'     => (int) $user->applicant_id,
                'email'   => $user->email,
                'name'    => $user->fullName,
                'type'    => $user->registrantType,
                'college' => (string) ($user->college ?? ''),
            ]);

            $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
            return redirect($frontendUrl . '/auth-establish.php?token=' . urlencode($authToken));

        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
            return redirect($frontendUrl . '/login.php?error=google_failed');
        }
    }

    private function generateUniqueStudentRegNo(): string
    {
        do {
            $regNo = 'G' . str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        } while (Applicant::where('studentRegNo', $regNo)->exists());

        return $regNo;
    }
}
