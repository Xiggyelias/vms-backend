<?php

namespace App\Exceptions;

use Exception;

class CustomException extends Exception
{
    protected $errorCode;
    protected $context;

    public function __construct(
        string $message = "",
        string $errorCode = "GENERAL_ERROR",
        array $context = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function toArray(): array
    {
        return [
            'error_code' => $this->errorCode,
            'message'    => $this->getMessage(),
            'context'    => $this->context,
        ];
    }
}







