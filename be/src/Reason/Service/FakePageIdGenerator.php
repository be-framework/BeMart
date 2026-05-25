<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;

use function bin2hex;
use function random_bytes;

final class FakePageIdGenerator implements PageIdGeneratorInterface
{
    #[Override]
    public function generate(): string
    {
        return 'pg-' . bin2hex(random_bytes(8));
    }
}
