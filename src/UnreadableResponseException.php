<?php

namespace OpenPix\PhpSdk;

use RuntimeException;
use Throwable;

/**
 * Thrown when the body of an API response could not be interpreted.
 *
 * A 2xx response with an unreadable body means the operation may have been
 * processed by the API even though the result could not be read, so callers
 * should reconcile (e.g. by `correlationID`) instead of blindly retrying.
 */
class UnreadableResponseException extends RuntimeException
{
    /**
     * HTTP status code of the response whose body could not be interpreted.
     *
     * @var int
     */
    private $statusCode;

    public function __construct(string $message, int $statusCode, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);

        $this->statusCode = $statusCode;
    }

    /**
     * Get the HTTP status code of the unreadable response.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
