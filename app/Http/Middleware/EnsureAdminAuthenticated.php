<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $frontendAdminLogin = rtrim((string) config('app.frontend_url'), '/') . '/admin-login.php';

        if (!session('is_admin') || !session('admin_id')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Admin authentication required.'], 401);
            }
            return redirect($frontendAdminLogin);
        }

        $sessionLifetime = config('session.lifetime') * 60;
        $loginTime = session('login_time', 0);

        if ((time() - $loginTime) > $sessionLifetime) {
            session()->flush();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Session expired. Please log in again.'], 401);
            }
            return redirect($frontendAdminLogin);
        }

        session(['login_time' => time()]);

        return $next($request);
    }
}
