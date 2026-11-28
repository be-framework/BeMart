<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Fake\Service;

use MyVendor\BeMart\Be\Reason\Service\InquiryTicketIssuerInterface;
use Override;

use function sprintf;

final class FakeInquiryTicketIssuer implements InquiryTicketIssuerInterface
{
    private int $issued = 0;

    #[Override]
    public function issue(): string
    {
        return sprintf('INQ-%04d', ++$this->issued);
    }
}
