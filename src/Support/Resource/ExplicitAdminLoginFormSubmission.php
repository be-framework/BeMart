<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use Override;

final class ExplicitAdminLoginFormSubmission implements AdminLoginFormSubmissionInterface
{
    #[Override]
    public function __invoke(string|null $mode): bool
    {
        return $mode !== null;
    }
}
