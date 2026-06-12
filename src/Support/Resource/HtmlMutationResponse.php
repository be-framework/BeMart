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
        unset($defaultCode);

        $ro->code = Code::SEE_OTHER;
        if ($location !== null) {
            $ro->headers['Location'] = $location;
        }
    }
}
