<?php

declare(strict_types=1);

namespace Application;

class ServiceLayer
{
    /**
     * @param array<array-key, mixed> $list
     */
    public function __construct(private readonly array $list = [])
    {
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getList(): array
    {
        return $this->list;
    }
}
