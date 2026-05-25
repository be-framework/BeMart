<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Service;

/**
 * Generates an opaque adminId for a newly-created admin — Wave 8
 * (doCreateMember). Distinct from {@see CustomerIdGeneratorInterface}
 * so the two id spaces stay separate (admins use an `ad…` prefix in
 * the fixture; customers do not).
 */
interface AdminIdGeneratorInterface
{
    /** @return non-empty-string */
    public function generate(): string;
}
