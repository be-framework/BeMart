<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Auth;

use RuntimeException;

/** `session_regenerate_id()` refused: the old id would keep identifying the new authentication state. */
final class SessionRotationException extends RuntimeException
{
}
