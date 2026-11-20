<?php

declare(strict_types=1);

namespace Core\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Sends a PSR-7 response through the SAPI: status line, headers (several Set-Cookie lines),
 * body in chunks. No body for HEAD and for 1xx/204/304 (RFC 9110 §6.4.1, §9.3.2).
 */
class Emitter
{
    public function __construct(private readonly int $chunkSize = 65536)
    {
    }

    public function emit(ResponseInterface $response, bool $withBody = true): void
    {
        $withBody = $withBody && !Status::isWithoutContent($response->getStatusCode());

        if (!headers_sent()) {
            header(sprintf(
                'HTTP/%s %d%s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase() !== '' ? ' ' . $response->getReasonPhrase() : ''
            ), true, $response->getStatusCode());

            foreach ($response->getHeaders() as $name => $values) {
                $replace = strtolower($name) !== 'set-cookie';
                foreach ($values as $value) {
                    header($name . ': ' . $value, $replace);
                    $replace = false;
                }
            }
            if (!$response->hasHeader('Content-Type')) {
                ini_set('default_mimetype', ''); // otherwise PHP adds "Content-Type: text/html"
            }
        }

        if (!$withBody) {
            return;
        }

        $body = $response->getBody();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        while (!$body->eof()) {
            $chunk = $body->read($this->chunkSize);
            if ($chunk === '') {
                break;
            }
            echo $chunk;
        }
    }
}
