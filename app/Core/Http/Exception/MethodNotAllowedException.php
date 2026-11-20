<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 405 Method Not Allowed. RFC 9110 §15.5.6: the response must contain Allow.
 */
class MethodNotAllowedException extends ClientErrorException
{
    /**
     * @param list<string> $allowedMethods
     */
    public function __construct(private readonly array $allowedMethods, ?string $detail = null, ?\Throwable $previous = null)
    {
        parent::__construct(Status::MethodNotAllowed, $detail, ['Allow' => implode(', ', $allowedMethods)], $previous);
    }

    /**
     * @return list<string>
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
