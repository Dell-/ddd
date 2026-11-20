<?php

declare(strict_types=1);

namespace Application\Action\Http;

use Core\Request\Http\Action;

class Index extends Action
{
    public function execute(): string
    {
        return static::class;
    }
}
