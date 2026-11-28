<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\OrderNoIssuerInterface;
use Override;

use function sprintf;

/** Numbers the orders of one instance 1, 2, ... in the 32 hex digits a real number has */
final class FakeOrderNoIssuer implements OrderNoIssuerInterface
{
    private int $issued = 0;

    /** @return non-empty-string */
    #[Override]
    public function issue(): string
    {
        return sprintf('%032x', ++$this->issued);
    }
}
