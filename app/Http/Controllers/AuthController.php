<?php

namespace App\Http\Controllers;

use App\Services\AuthenticationService;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AuthController extends Controller
{
    protected AuthenticationService $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function showAdminLoginForm(): View
    {
        return view('auth.admin-login');
    }

    public function login(LoginRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $user = $this->authService->authenticateUser(
                $request->input('identifier'),
                $request->input('password'),
                $request->input('userType')
            );

            $this->authService->createUserSession($user);

            if ($request->expectsJson()) {
                return $this->ok([
                    'data' => [
                        'user_id' => $user->applicant_id,
                        'user_type' => $user->registrantType,
                    ],
                ], 'Login successful.');
            }

            return redirect(rtrim((string) config('app.frontend_url'), '/') . '/user-dashboard.php');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->fail('Login failed.', 422, ['errors' => $e->errors()]);
            }
            return back()->withErrors($e->errors())->withInput();
        }
    }

    public function adminLogin(AdminLoginRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $admin = $this->authService->authenticateAdmin(
                $request->input('username'),
                $request->input('password')
            );

            $this->authService->createAdminSession($admin);

            if ($request->expectsJson()) {
                return $this->ok([
                    'data' => [
                        'admin_id' => $admin->id,
                    ],
                ], 'Admin login successful.');
            }

            return redirect(rtrim((string) config('app.frontend_url'), '/') . '/admin-dashboard.php');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return $this->fail('Admin login failed.', 422, ['errors' => $e->errors()]);
            }
            return back()->withErrors($e->errors())->withInput();
        }
    }

    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        $this->authService->logout($request, 'web');
        if ($request->expectsJson()) {
            return $this->ok([], 'Logged out successfully.');
        }

        return redirect(rtrim((string) config('app.frontend_url'), '/') . '/login.php');
    }

    public function adminLogout(Request $request): RedirectResponse|JsonResponse
    {
        $this->authService->logout($request, 'admin');
        if ($request->expectsJson()) {
            return $this->ok([], 'Admin logged out successfully.');
        }

        return redirect(rtrim((string) config('app.frontend_url'), '/') . '/admin-login.php');
    }
}
