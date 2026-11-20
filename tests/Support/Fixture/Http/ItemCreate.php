<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Exception\UnprocessableContentException;
use Core\Http\Message\ServerRequestInterface;
use Core\Route\UrlGeneratorInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Creates item 42: 201 with Location, 422 with validation errors.
 */
final class ItemCreate extends AbstractAction
{
    public function __construct(private readonly UrlGeneratorInterface $urls)
    {
    }

    #[\Override]
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $name = $request->getBodyParam('name');
        if (!is_string($name) || trim($name) === '') {
            throw new UnprocessableContentException(['name' => 'The name is required.']);
        }

        return $this->created($this->urls->generate('item.show', ['id' => 42]), ['id' => 42, 'name' => $name]);
    }
}
