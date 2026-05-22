<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('logged_in') || !session('user_id')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Authentication required.'], 401);
            }
            
            return redirect()->route('auth.login')
                ->with('error', 'Please log in to continue.')
                ->with('redirect', $request->fullUrl());
        }

        $sessionLifetime = config('session.lifetime') * 60;
        $loginTime = session('login_time', 0);
        
        if ((time() - $loginTime) > $sessionLifetime) {
            session()->flush();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Session expired. Please log in again.'], 401);
            }
            return redirect()->route('auth.login')->with('error', 'Your session has expired. Please log in again.');
        }

        session(['login_time' => time()]);

        return $next($request);
    }
}
