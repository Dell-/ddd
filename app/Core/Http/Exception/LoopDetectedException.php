<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 508 Loop Detected
 */
class LoopDetectedException extends ServerErrorException
{
    /**
     * @param array<string, string|list<string>> $headers
     */
    public function __construct(?string $detail = null, ?\Throwable $previous = null, array $headers = [])
    {
        parent::__construct(Status::LoopDetected, $detail, $headers, $previous);
    }
}
