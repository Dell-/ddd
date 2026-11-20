<?php

declare(strict_types=1);

namespace Core\Http\Exception;

use Core\Http\Status;

/**
 * 426 Upgrade Required. RFC 9110 §15.5.22: the response must contain Upgrade.
 */
class UpgradeRequiredException extends ClientErrorException
{
    public function __construct(string $upgrade, ?string $detail = null, ?\Throwable $previous = null)
    {
        parent::__construct(Status::UpgradeRequired, $detail, ['Upgrade' => $upgrade, 'Connection' => 'Upgrade'], $previous);
    }
}
