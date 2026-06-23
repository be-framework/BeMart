<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Error;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

/**
 * Post/Redirect-style 303 response for the html context.
 *
 * EC-CUBE firewalls customer pages (`^/mypage`) behind the security
 * component: an anonymous visitor is not shown a 401 error page but is
 * redirected to the login form. The JSON / HAL contexts keep the API-
 * faithful 401 ({@see AppThrowableHandler}); only the browser context
 * turns the AUTHN failure into this redirect so the journey can recover.
 *
 * Like {@see HtmlErrorPage} the body is empty and `$view` is set to ''
 * so the responder emits the bare redirect without round-tripping a
 * renderer.
 */
final class HtmlRedirect extends ResourceObject
{
    public function __construct(string $location)
    {
        $this->code = Code::SEE_OTHER;
        $this->headers = ['Location' => $location];
        $this->view = '';
    }
}
