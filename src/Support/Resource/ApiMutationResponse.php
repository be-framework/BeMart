<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Override;

final class ApiMutationResponse implements MutationResponseInterface
{
    #[Override]
    public function __invoke(ResourceObject $ro, int $defaultCode, string|null $location = null): void
    {
        $ro->code = $location !== null && $defaultCode === Code::OK ? Code::SEE_OTHER : $defaultCode;
        if ($location !== null) {
            $ro->headers['Location'] = $location;
        }
    }
}
