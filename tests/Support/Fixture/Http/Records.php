<?php

declare(strict_types=1);

namespace Tests\Support\Fixture\Http;

use Core\Http\Action\AbstractAction;
use Core\Http\Message\ServerRequestInterface;
use Core\Http\Response\JsonLinesResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * GET: three records as NDJSON (?seq=1: application/json-seq). POST: counts the received records.
 */
final class Records extends AbstractAction
{
    #[\Override]
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->isMethod('POST')) {
            $records = $request->getParsedBody();

            return $this->json(['count' => is_array($records) ? count($records) : 0, 'records' => $records]);
        }

        return new JsonLinesResponse(
            [['id' => 1], ['id' => 2], ['id' => 3]],
            sequence: $request->getQueryParam('seq') !== null
        );
    }
}
