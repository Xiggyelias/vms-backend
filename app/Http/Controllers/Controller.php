<?php

namespace App\Http\Controllers;

use App\Support\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests, ApiResponse;

    /**
     * Build a short-lived HMAC-signed token for cross-service session handoff.
     * Expires in 60 seconds — verify immediately on the receiving end.
     */
    protected function buildAuthToken(array $claims): string
    {
        $claims['exp'] = time() + 60;
        $payload = rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=');
        $sig = hash_hmac('sha256', $payload, (string) config('app.auth_shared_secret', ''));
        return $payload . '.' . $sig;
    }

    /** Returns the decoded claims array, or null if the token is invalid or expired. */
    protected function verifyAuthToken(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }
        [$payload, $sig] = $parts;
        $secret = (string) config('app.auth_shared_secret', '');
        if ($secret === '') {
            return null;
        }
        if (!hash_equals(hash_hmac('sha256', $payload, $secret), $sig)) {
            return null;
        }
        $claims = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);
        if (!is_array($claims) || ($claims['exp'] ?? 0) < time()) {
            return null;
        }
        return $claims;
    }
}
