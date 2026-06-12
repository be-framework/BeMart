<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use Override;

final class HtmlAdminLoginFormSubmission implements AdminLoginFormSubmissionInterface
{
    #[Override]
    public function __invoke(string|null $mode): bool
    {
        unset($mode);

        return true;
    }
}
