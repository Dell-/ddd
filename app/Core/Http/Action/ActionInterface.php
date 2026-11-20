<?php

declare(strict_types=1);

namespace Core\Http\Action;

use Core\Http\Message\ServerRequestInterface;
use Core\Http\View\View;
use Psr\Http\Message\ResponseInterface;

/**
 * Convenience action: returns any result, the Responder turns it into a response.
 * For full control implement Psr\Http\Server\RequestHandlerInterface instead (both are supported).
 *
 * Results: ResponseInterface (as it is), View (negotiated), string/Stringable (HTML),
 * null (204), arrays, JsonSerializable and other data (negotiated View: JSON, XML, ...).
 */
interface ActionInterface
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface|View|\Stringable|\JsonSerializable|array|string|int|float|bool|null;
}
