<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use BEAR\Resource\ResourceObject;

interface MutationResponseInterface
{
    public function __invoke(ResourceObject $ro, int $defaultCode, string|null $location = null): void;

    public function redirectOnSuccess(ResourceObject $ro, string $location): void;
}
