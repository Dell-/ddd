<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Exception\NotFoundException;
use Core\Http\Header\DispositionType;
use Core\Http\Message\ServerRequestInterface;
use Core\Http\Response\FileResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * GET /download/hello.txt (?inline=1): a file from tests/Support/Data/files.
 */
final class Download extends AbstractAction
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $path = dirname(__DIR__, 2) . '/Data/files/' . basename((string) $request->getAttribute('name'));
        if (!is_file($path)) {
            throw new NotFoundException('No such file.');
        }

        return new FileResponse(
            $path,
            'Привіт ' . basename($path),
            $request->getQueryParam('inline') !== null ? DispositionType::Inline : DispositionType::Attachment
        );
    }
}
