<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\PageIdGeneratorInterface;
use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Override;

use function bin2hex;
use function random_bytes;

final class FakePageIdGenerator implements PageIdGeneratorInterface
{
    #[Override]
    public function generate(): AllocatedId
    {
        return new AllocatedId('pg-' . bin2hex(random_bytes(8)));
    }
}
