<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;
use MyVendor\BeMart\Be\Exception\JobFormatException;

/**
 * Job — mtb_job code. 1..18 per EC-CUBE 4.3 master.
 */
final class Job
{
    #[Validate]
    public function validate(int|null $job): void
    {
        if ($job === null) {
            return;
        }

        if ($job < 1 || $job > 18) {
            throw new JobFormatException();
        }
    }
}
