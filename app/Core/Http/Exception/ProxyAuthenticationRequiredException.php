<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 407 Proxy Authentication Required. RFC 9110 §15.5.8: requires a Proxy-Authenticate challenge.
 */
class ProxyAuthenticationRequiredException extends ClientErrorException
{
    /**
     * @param string|list<string> $challenge
     */
    public function __construct(string|array $challenge = 'Basic', ?string $detail = null, ?\Throwable $previous = null)
    {
        parent::__construct(Status::ProxyAuthenticationRequired, $detail, ['Proxy-Authenticate' => $challenge], $previous);
    }
}
