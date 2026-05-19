<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goShoppingLogin — 購入ログイン (Wave 3H pure renderer).
 *
 * Pure form-info endpoint: no Be Framework, no domain logic, no Reasons.
 * Anonymous-accessible (this IS the unauthenticated branch of the
 * checkout flow). Maps to `page://self/shopping/login`.
 *
 * Reached when an anonymous visitor hits `goShopping`. Three exits:
 * member login (doLogin), customer registration (goCustomerRegistration),
 * or non-member purchase (goShoppingNonMember). This renderer just
 * presents the choice surface — no fields of its own.
 *
 * Coexists with `Resource\Page\Shopping\Checkout.php` (Pilot 5) and
 * `Shopping\NonMember.php` (Wave 7W).
 */
class Login extends ResourceObject
{
    #[Link(rel: 'doLogin', href: 'page://self/login', method: 'post')]
    #[Link(rel: 'goCustomerRegistration', href: 'page://self/entry')]
    #[Link(rel: 'goShoppingNonMember', href: 'page://self/shopping/non-member')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goShoppingLogin',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => null,
            'links' => [
                'doLogin' => 'page://self/login',
                'goCustomerRegistration' => 'page://self/entry',
                'goShoppingNonMember' => 'page://self/shopping/non-member',
            ],
        ];

        return $this;
    }
}
