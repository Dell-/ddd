<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 422 Unprocessable Content: the content is well-formed but invalid (RFC 9110 §15.5.21).
 * Validation errors go to the "errors" problem details member.
 */
class UnprocessableContentException extends ClientErrorException
{
    /**
     * @param array<string, string|list<string>> $errors field => message(s)
     */
    public function __construct(private readonly array $errors = [], ?string $detail = null, ?\Throwable $previous = null)
    {
        parent::__construct(
            Status::UnprocessableContent,
            $detail ?? ($errors === [] ? null : 'The content contains invalid values.'),
            [],
            $previous,
            extensions: $errors === [] ? [] : ['errors' => $errors]
        );
    }

    /**
     * @return array<string, string|list<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
