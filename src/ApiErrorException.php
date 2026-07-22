<?php

namespace OpenPix\PhpSdk;

use Exception;
use Throwable;

/**
 * Thrown when the API rejects a request.
 *
 * Carries the HTTP status code and the decoded response body so callers can
 * classify the failure by status instead of matching the upstream message,
 * which is not a stable contract.
 */
class ApiErrorException extends Exception
{
    /**
     * HTTP status code of the rejected response.
     *
     * @var int
     */
    private $statusCode;

    /**
     * Decoded response body, when it could be read.
     *
     * @var array<string, mixed>
     */
    private $body;

    /**
     * @param array<string, mixed> $body Decoded response body.
     */
    public function __construct(
        string $message,
        int $statusCode = 0,
        array $body = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->statusCode = $statusCode;
        $this->body = $body;
    }

    /**
     * Get the HTTP status code of the rejected response.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the decoded response body.
     *
     * @return array<string, mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }
}
