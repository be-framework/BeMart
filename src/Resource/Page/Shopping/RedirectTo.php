<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

use function str_starts_with;

/**
 * EC-CUBE shopping_redirect_to — redirect inside the checkout flow.
 *
 * The original controller stores the current order form values, recalculates
 * the PurchaseFlow and redirects to the submitted `redirect_to` route.  The
 * BeMart slice does not yet persist a mutable checkout draft, but this keeps
 * the route explicit and constrained to local checkout paths instead of using
 * the catch-all ActionRedirect resource.
 */
class RedirectTo extends ResourceObject
{
    #[Link(rel: 'goShopping', href: 'page://self/shopping')]
    #[CsrfProtected]
    public function onPost(string|null $redirectTo = null): static
    {
        $this->code = Code::OK;
        $this->headers['Location'] = $this->safeCheckoutPath($redirectTo);
        $this->body = [
            'transitionId' => 'doShoppingRedirectTo',
            'redirectTo' => $redirectTo,
        ];

        return $this;
    }

    private function safeCheckoutPath(string|null $redirectTo): string
    {
        if ($redirectTo === null || $redirectTo === '') {
            return '/shopping';
        }

        if (! str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, '//')) {
            return '/shopping/error';
        }

        if (! str_starts_with($redirectTo, '/shopping')) {
            return '/shopping/error';
        }

        return $redirectTo;
    }
}
