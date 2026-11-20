<?php

declare(strict_types=1);

namespace Core\Http\Header;

/**
 * Content-Disposition types (RFC 6266 §4.2, RFC 7578 §4.2).
 */
enum DispositionType: string
{
    /** Show in the browser */
    case Inline = 'inline';

    /** Download ("Save as") */
    case Attachment = 'attachment';

    /** Part of multipart/form-data */
    case FormData = 'form-data';
}
