<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/** Issues the ticket id a submitted inquiry is answered with */
interface InquiryTicketIssuerInterface
{
    public function issue(): string;
}
