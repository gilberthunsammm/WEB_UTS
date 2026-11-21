<?php

namespace App\Services;

use Exception;

/**
 * Class GoogleBooksException
 * Menangani error khusus terkait komunikasi dan response Google Books API
 */
class GoogleBooksException extends Exception
{
    private int $httpCode;
    private string $friendlyMessage;

    public function __construct(
        string $message,
        int $httpCode = 0,
        string $friendlyMessage = '',
        ?Exception $previous = null
    ) {
        parent::__construct($message, $httpCode, $previous);
        $this->httpCode = $httpCode;
        $this->friendlyMessage = $friendlyMessage ?: $message;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getFriendlyMessage(): string
    {
        return $this->friendlyMessage;
    }
}
