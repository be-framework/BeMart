<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use BEAR\Resource\ResourceObject;
use Override;

final class ApiMutationResponse implements MutationResponseInterface
{
    #[Override]
    public function __invoke(ResourceObject $ro, int $defaultCode, string|null $location = null): void
    {
        $ro->code = $defaultCode;
        if ($location !== null) {
            $ro->headers['Location'] = $location;
        }
    }

    #[Override]
    public function redirectOnSuccess(ResourceObject $ro, string $location): void
    {
        unset($ro, $location);
    }
}
