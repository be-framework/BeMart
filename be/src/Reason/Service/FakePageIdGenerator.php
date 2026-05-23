<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use MyVendor\BeMart\Be\Reason\Query\Result\GeneratedId;
use Override;

use function bin2hex;
use function random_bytes;

final class FakePageIdGenerator implements PageIdGeneratorInterface
{
    #[Override]
    public function generate(): GeneratedId
    {
        return new GeneratedId('pg-' . bin2hex(random_bytes(8)));
    }
}
