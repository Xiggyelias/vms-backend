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
        // These endpoints are called by the frontend JavaScript (potentially from a
        // different domain) using a Google ID token as their own form of authentication.
        // CSRF protection is handled by verifying the Google token server-side instead.
        'google_auth.php',
        'finalize_role.php',
        '/google_auth.php',
        '/finalize_role.php',
    ];
}
