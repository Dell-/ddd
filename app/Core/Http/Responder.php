<?php

declare(strict_types=1);

namespace Core\Http;

use Core\Http\Message\ServerRequestInterface;
use Core\Http\Response\EmptyResponse;
use Core\Http\Response\HtmlResponse;
use Core\Http\View\View;
use Core\Http\View\ViewRenderer;
use Psr\Http\Message\ResponseInterface;

/**
 * Turns an action result into a response (see ActionInterface).
 */
final class Responder
{
    public function __construct(private readonly ViewRenderer $viewRenderer = new ViewRenderer())
    {
    }

    public function respond(mixed $result, ServerRequestInterface $request): ResponseInterface
    {
        return match (true) {
            $result instanceof ResponseInterface => $result,
            $result instanceof View => $this->viewRenderer->render($result, $request),
            $result === null => new EmptyResponse(),
            is_string($result), $result instanceof \Stringable && !$result instanceof \JsonSerializable => new HtmlResponse($result),
            default => $this->viewRenderer->render(new View($result), $request),
        };
    }
}
