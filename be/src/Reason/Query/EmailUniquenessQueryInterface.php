<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\EmailUniqueness;
use Ray\MediaQuery\Annotation\DbQuery;

interface EmailUniquenessQueryInterface
{
    #[DbQuery('customer_email_exists')]
    public function item(string $email): EmailUniqueness;
}
