<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PUT /files/{name}: the body is the file; stores it and answers 201 with its description.
 */
final class RawUpload extends AbstractAction
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $file = $request->getBodyAsFile((string) $request->getAttribute('name'));
        $directory = sys_get_temp_dir() . '/ddd-uploads';
        $path = $file->store($directory);
        $stored = (string) file_get_contents($path);
        unlink($path);

        return $this->created('/files/' . rawurlencode((string) $request->getAttribute('name')), [
            'name' => $file->getClientFilename(),
            'clientType' => $file->getClientMediaType(),
            'type' => $file->getMediaType(),
            'size' => strlen($stored),
            'sha256' => hash('sha256', $stored),
            'storedAs' => pathinfo($path, PATHINFO_EXTENSION),
        ]);
    }
}
