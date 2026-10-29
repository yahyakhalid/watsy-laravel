<?php

namespace Watsy\Laravel;

/**
 * An error from the Watsy API: a stable `errorCode` (e.g. insufficient_balance),
 * the HTTP status, the message in English and Arabic, and the request id to
 * quote to support.
 */
class WatsyException extends \RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $status,
        string $message,
        public readonly ?string $messageAr = null,
        public readonly ?string $detail = null,
        public readonly ?string $requestId = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
    }
}
