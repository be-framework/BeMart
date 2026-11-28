<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

use Override;

use function uniqid;

final class UniqidInquiryTicketIssuer implements InquiryTicketIssuerInterface
{
    #[Override]
    public function issue(): string
    {
        return uniqid('INQ-', true);
    }
}
