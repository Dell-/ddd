<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Message\ServerRequestInterface;
use Core\Http\Message\UploadedFile;

/**
 * Describes the uploaded files: multipart (any method) and data URIs in JSON ("avatar": "data:...").
 */
final class Upload extends AbstractAction
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function __invoke(ServerRequestInterface $request): array
    {
        $describe = static fn (UploadedFile $file): array => [
            'ok' => $file->isOk(),
            'name' => $file->getClientFilename(),
            'clientType' => $file->getClientMediaType(),
            'type' => $file->isOk() ? $file->getMediaType() : null,
            'size' => $file->getSize(),
            'content' => $file->isOk() ? $file->getContents() : null,
        ];

        $files = [];
        foreach (['document', 'photos', 'meta.attachment'] as $field) {
            foreach ($request->getUploadedFileList($field) as $file) {
                $files[$field][] = $describe($file);
            }
        }
        $dataUri = $request->getBodyParam('avatar');
        if (is_string($dataUri)) {
            $files['avatar'][] = $describe(UploadedFile::fromDataUri($dataUri, 'avatar'));
        }

        return [
            'method' => $request->getMethod(),
            'title' => $request->getBodyParam('title'),
            'files' => $files,
        ];
    }
}
