<?php

declare(strict_types=1);

namespace Core\Http\Header;

/**
 * SameSite cookie attribute (RFC 6265bis §4.1.2.7).
 */
enum SameSite: string
{
    case Strict = 'Strict';
    case Lax = 'Lax';
    /** Requires Secure */
    case None = 'None';
}
