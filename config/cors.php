<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Restrict cross-origin requests to known front-end origins only.
    | Set CORS_ALLOWED_ORIGINS in .env to a comma-separated list of origins,
    | e.g. "https://yourdomain.com,https://www.yourdomain.com".
    | Leave blank to reject all cross-origin requests.
    |
    */

    'paths' => ['api/*', 'auth/*', 'google_auth.php', 'finalize_role.php'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(
        array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'X-CSRF-TOKEN', 'Authorization'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,

];
