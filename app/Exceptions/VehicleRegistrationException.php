<?php

namespace App\Exceptions;

class VehicleRegistrationException extends CustomException
{
    public static function vehicleLimitExceeded(int $currentCount, int $maxLimit): self
    {
        return new self(
            "Vehicle limit exceeded. You have {$currentCount} vehicles and the maximum allowed is {$maxLimit}.",
            "VEHICLE_LIMIT_EXCEEDED",
            ['current_count' => $currentCount, 'max_limit' => $maxLimit]
        );
    }

    public static function registrationNumberExists(string $regNumber): self
    {
        return new self(
            "Vehicle with registration number '{$regNumber}' already exists.",
            "REGISTRATION_NUMBER_EXISTS",
            ['registration_number' => $regNumber]
        );
    }

    public static function permissionDenied(string $action): self
    {
        return new self(
            "You do not have permission to {$action}.",
            'PERMISSION_DENIED',
            ['action' => $action]
        );
    }
}







