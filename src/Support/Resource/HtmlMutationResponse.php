<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Override;

final class HtmlMutationResponse implements MutationResponseInterface
{
    #[Override]
    public function __invoke(ResourceObject $ro, int $defaultCode, string|null $location = null): void
    {
        $ro->code = Code::SEE_OTHER;
        if ($location !== null) {
            $ro->headers['Location'] = $location;
        }
    }

    #[Override]
    public function redirectOnSuccess(ResourceObject $ro, string $location): void
    {
        if ($ro->code >= 400) {
            return;
        }

        $ro->code = Code::SEE_OTHER;
        $ro->headers['Location'] = $location;
    }
}
