<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use Illuminate\Support\Facades\Auth;
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
                    return redirect()->route('auth.login')
                        ->with('error', 'Please use your university email address.');
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

            if (!in_array($user->registrantType, ['student', 'staff'], true)) {
                return redirect()->route('auth.login')
                    ->with('error', 'Google login is only available for students and staff.');
            }

            if (($user->status ?? 'active') === 'suspended') {
                return redirect()->route('auth.login')
                    ->with('error', 'Your account has been suspended. Please contact the administrator.');
            }

            session([
                'user_id'      => $user->applicant_id,
                'user_email'   => $user->email,
                'user_name'    => $user->fullName,
                'user_type'    => $user->registrantType,
                'user_college' => $user->college,
                'logged_in'    => true,
                'login_time'   => time(),
            ]);

            Auth::guard('web')->login($user);

            return redirect()->route('dashboard.user')
                ->with('success', 'Logged in successfully with Google.');

        } catch (\Throwable $e) {
            Log::error('Google OAuth callback failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return redirect()->route('auth.login')
                ->with('error', 'Google login failed. Please try again or use your email and password.');
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
