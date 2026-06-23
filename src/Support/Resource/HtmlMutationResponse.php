<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Override;

use function str_starts_with;

final class HtmlMutationResponse implements MutationResponseInterface
{
    #[Override]
    public function __invoke(ResourceObject $ro, int $defaultCode, string|null $location = null): void
    {
        unset($defaultCode);

        $ro->code = Code::SEE_OTHER;
        if ($location !== null) {
            $ro->headers['Location'] = $location;

            // EC-CUBE admin save feedback: every admin write controller
            // calls addSuccess('admin.common.save_complete', 'admin'),
            // surfaced as the「保存しました」success banner on the
            // POST-redirect-GET target (see AdminFlash). Storefront
            // redirects (/cart, /mypage/...) keep no admin flash.
            if (str_starts_with($location, '/admin/')) {
                AdminFlash::add(AdminFlash::SAVE_COMPLETE);
            }
        }
    }
}
