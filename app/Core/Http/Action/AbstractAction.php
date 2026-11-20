<?php

declare(strict_types=1);

namespace Core\Http\Action;

use Core\Http\Header\DispositionType;
use Core\Http\Response\EmptyResponse;
use Core\Http\Response\FileResponse;
use Core\Http\Response\HtmlResponse;
use Core\Http\Response\JsonResponse;
use Core\Http\Response\RedirectResponse;
use Core\Http\Response\TextResponse;
use Core\Http\Response\XmlResponse;
use Core\Http\Status;
use Core\Http\View\View;
use Psr\Http\Message\ResponseInterface;

/**
 * Base action with response helpers. Dependencies (repositories, UrlGeneratorInterface, ...) are
 * constructor arguments, autowired by the container.
 *
 *     final class ShowItem extends AbstractAction
 *     {
 *         public function __invoke(ServerRequestInterface $request): View
 *         {
 *             $item = $this->items->find((int) $request->getAttribute('id')) ?? throw new NotFoundException();
 *             return $this->view($item, 'item/show');   // JSON, XML or HTML by Accept
 *         }
 *     }
 */
abstract class AbstractAction implements ActionInterface
{
    /**
     * Data in the format the client prefers (Accept), with an optional HTML template.
     *
     * @param array<string, string|list<string>> $headers
     */
    protected function view(mixed $data = null, ?string $template = null, Status|int $status = Status::Ok, array $headers = []): View
    {
        return new View($data, $template, $status, $headers);
    }

    /**
     * @param array<string, string|list<string>> $headers
     */
    protected function json(mixed $data, Status|int $status = Status::Ok, array $headers = []): ResponseInterface
    {
        return new JsonResponse($data, $status, $headers);
    }

    protected function html(string|\Stringable $html, Status|int $status = Status::Ok): ResponseInterface
    {
        return new HtmlResponse($html, $status);
    }

    protected function text(string|\Stringable $text, Status|int $status = Status::Ok): ResponseInterface
    {
        return new TextResponse($text, $status);
    }

    protected function xml(mixed $xml, Status|int $status = Status::Ok): ResponseInterface
    {
        return new XmlResponse($xml, $status);
    }

    /**
     * 201 Created with the Location of the new resource (RFC 9110 §15.3.2).
     */
    protected function created(string $location, mixed $data = null): ResponseInterface
    {
        return $data === null
            ? new EmptyResponse(Status::Created, ['Location' => $location])
            : new JsonResponse($data, Status::Created, ['Location' => $location]);
    }

    /**
     * 202 Accepted: the request will be processed later, $location points to its status.
     */
    protected function accepted(?string $location = null, mixed $data = null): ResponseInterface
    {
        $headers = $location === null ? [] : ['Location' => $location];

        return $data === null ? new EmptyResponse(Status::Accepted, $headers) : new JsonResponse($data, Status::Accepted, $headers);
    }

    protected function noContent(): ResponseInterface
    {
        return new EmptyResponse();
    }

    /**
     * 303 See Other by default: the right answer to a successful form POST (RFC 9110 §15.4.4).
     */
    protected function redirect(string $location, Status|int $status = Status::SeeOther): ResponseInterface
    {
        return new RedirectResponse($location, $status);
    }

    protected function file(string $path, ?string $filename = null, DispositionType $disposition = DispositionType::Attachment): ResponseInterface
    {
        return new FileResponse($path, $filename, $disposition);
    }
}
