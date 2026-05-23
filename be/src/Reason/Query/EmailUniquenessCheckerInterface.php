<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\EmailUniqueness;
use Ray\MediaQuery\Annotation\DbQuery;

interface EmailUniquenessCheckerInterface
{
    /** Build the duplicate-email BDR; callers invoke EmailUniqueness::assertUnique(). */
    #[DbQuery('customer_email_exists')]
    public function check(string $email): EmailUniqueness;
}
