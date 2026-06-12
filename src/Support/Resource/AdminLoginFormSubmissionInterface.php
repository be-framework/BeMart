<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

interface AdminLoginFormSubmissionInterface
{
    public function __invoke(string|null $mode): bool;
}
