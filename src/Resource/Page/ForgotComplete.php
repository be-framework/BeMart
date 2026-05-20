<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * EC-CUBE goForgotComplete — パスワード再発行(メール送信完了) (Phase 3
 * pure renderer).
 *
 * Pure renderer: no Be Framework, no domain logic, no Reasons — the same
 * shape as {@see Products}. EC-CUBE shows `Forgot/complete.twig` after a
 * successful `doRequestPasswordReset`; it is a static confirmation page
 * with NO form (data-page recipe).
 *
 * Anonymous-accessible (returns 200 regardless of session state). Maps
 * to `page://self/forgot-complete`. The companion {@see ForgotPassword}
 * resource owns the actual reset-request domain logic; this resource
 * carries only the confirmation page's hypermedia surface — the page
 * itself is static text ported from EC-CUBE's template.
 *
 * Why a dedicated renderer (not a branch of ForgotPassword): EC-CUBE's
 * `doRequestPasswordReset` controller renders `Forgot/index.twig` on the
 * request screen and `Forgot/complete.twig` on completion — two distinct
 * templates, two distinct pages. BeMart's `ForgotPassword::onPost` is the
 * anti-enumeration request endpoint (uniform 200); this resource is the
 * separate confirmation page so each template has a 1:1 resource.
 *
 * @see #PasswordResetRequested in alps.json
 */
class ForgotComplete extends ResourceObject
{
    /**
     * EC-CUBE goForgotComplete — render the reset-mail-sent confirmation
     * page scaffolding.
     */
    #[Link(rel: 'goLogin', href: 'page://self/login')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goForgotComplete',
            'fields' => [],
            'submitTo' => null,
            'links' => [
                'goLogin' => 'page://self/login',
                'goTop' => 'page://self/',
            ],
        ];

        return $this;
    }
}
