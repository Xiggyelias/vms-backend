<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // These endpoints are called by the frontend JavaScript using their own
        // authentication mechanism (Google token or HMAC token), so CSRF is not needed.
        'google_auth.php',
        'finalize_role.php',
        'auth-sync.php',
        '/google_auth.php',
        '/finalize_role.php',
        '/auth-sync.php',
    ];
}
