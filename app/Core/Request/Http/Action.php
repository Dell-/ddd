<?php

declare(strict_types=1);

namespace Core\Request\Http;

use Core\Request\RequestInterface;
use Core\Service\ActionInterface;

/**
 * Base class of HTTP actions.
 */
abstract class Action implements ActionInterface
{
    public function __construct(protected readonly RequestInterface $request)
    {
    }
}
