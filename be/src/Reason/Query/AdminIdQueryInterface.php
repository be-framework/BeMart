<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use MyVendor\BeMart\Be\Reason\Query\Result\AllocatedId;
use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Generates an opaque adminId for a newly-created admin — Wave 8
 * (doCreateMember). Distinct from {@see CustomerIdQueryInterface}
 * so the two id spaces stay separate (admins use an `ad…` prefix in
 * the fixture; customers do not).
 */
interface AdminIdQueryInterface
{
    #[DbQuery('admin_next_id')]
    public function next(): AllocatedId;
}
