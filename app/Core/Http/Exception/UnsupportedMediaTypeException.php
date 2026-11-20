<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 415 Unsupported Media Type. RFC 9110 §15.5.16: the Accept header may list the supported types.
 */
class UnsupportedMediaTypeException extends ClientErrorException
{
    /**
     * @param list<string> $supported
     */
    public function __construct(private readonly array $supported = [], ?string $detail = null, ?\Throwable $previous = null)
    {
        parent::__construct(
            Status::UnsupportedMediaType,
            $detail,
            $supported === [] ? [] : ['Accept' => implode(', ', $supported)],
            $previous
        );
    }

    /**
     * @return list<string>
     */
    public function getSupported(): array
    {
        return $this->supported;
    }
}
