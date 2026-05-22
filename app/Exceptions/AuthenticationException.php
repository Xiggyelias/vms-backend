<?php

namespace App\Exceptions;

class AuthenticationException extends CustomException
{
    public static function invalidCredentials(): self
    {
        return new self(
            "Invalid username or password.",
            "INVALID_CREDENTIALS"
        );
    }

    public static function userNotFound(): self
    {
        return new self(
            "User not found or invalid user type.",
            "USER_NOT_FOUND"
        );
    }

    public static function accountSuspended(): self
    {
        return new self(
            "Your account has been suspended. Please contact administrator.",
            "ACCOUNT_SUSPENDED"
        );
    }

    public static function tooManyAttempts(): self
    {
        return new self(
            "Too many login attempts. Please try again later.",
            "TOO_MANY_ATTEMPTS"
        );
    }
}











