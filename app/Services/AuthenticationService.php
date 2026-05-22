<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticationService
{
    public function authenticateUser(string $identifier, string $password, string $userType): Applicant
    {
        $user = Applicant::where('registrantType', $userType)
            ->where(function ($q) use ($identifier, $userType) {
                $q->where('email', $identifier);
                if ($userType === 'student') {
                    $q->orWhere('studentRegNo', $identifier);
                } elseif ($userType === 'staff') {
                    $q->orWhere('staffsRegNo', $identifier);
                }
            })
            ->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'identifier' => 'User not found or invalid user type.',
            ]);
        }

        if (($user->status ?? 'active') === 'suspended') {
            throw ValidationException::withMessages([
                'identifier' => 'Your account has been suspended. Please contact the administrator.',
            ]);
        }

        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Invalid password.',
            ]);
        }

        return $user;
    }

    public function authenticateAdmin(string $username, string $password): Admin
    {
        $admin = Admin::where('username', $username)->first();

        if (!$admin) {
            throw ValidationException::withMessages([
                'username' => 'Invalid username or password.',
            ]);
        }

        if (!Hash::check($password, $admin->password)) {
            throw ValidationException::withMessages([
                'password' => 'Invalid username or password.',
            ]);
        }

        return $admin;
    }

    public function createUserSession(Applicant $user): void
    {
        $user->update(['last_login' => now()]);

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
    }

    public function createAdminSession(Admin $admin): void
    {
        session([
            'admin_id'       => $admin->id,
            'admin_username' => $admin->username,
            'is_admin'       => true,
            'logged_in'      => true,
            'login_time'     => time(),
        ]);

        Auth::guard('admin')->login($admin);
    }

    public function logout(Request $request, string $guard = 'web'): void
    {
        Auth::guard($guard)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
