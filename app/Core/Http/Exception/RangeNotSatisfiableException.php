<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 416 Range Not Satisfiable. RFC 9110 §15.5.17: Content-Range: bytes * /<complete length>.
 */
class RangeNotSatisfiableException extends ClientErrorException
{
    public function __construct(int $completeLength, ?string $detail = null, ?\Throwable $previous = null)
    {
        parent::__construct(Status::RangeNotSatisfiable, $detail, ['Content-Range' => 'bytes */' . $completeLength], $previous);
    }
}
