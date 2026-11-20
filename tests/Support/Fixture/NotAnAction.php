<?php

declare(strict_types=1);

namespace Tests\Support\Fixture;

use Core\Request\RequestInterface;

final class NotAnAction
{
    public function __construct(public readonly RequestInterface $request)
    {
    }
}
