<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;

use function bin2hex;
use function random_bytes;

final class FakeTagIdGenerator implements TagIdGeneratorInterface
{
    #[Override]
    public function generate(): string
    {
        return 'tg-' . bin2hex(random_bytes(8));
    }
}
