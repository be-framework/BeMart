<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Provide\Error;

use BEAR\Resource\ResourceObject;

/**
 * Pre-rendered HTML error page for the html context.
 *
 * The Twig output is assigned to {@see ResourceObject::$view}, so the
 * responder emits it verbatim with a `text/html` content type and never
 * round-trips through a renderer — a failure while building the error
 * page can therefore not recurse back through rendering.
 */
final class HtmlErrorPage extends ResourceObject
{
    public function __construct(int $code, string $html)
    {
        $this->code = $code;
        $this->headers = ['Content-Type' => 'text/html; charset=utf-8'];
        $this->view = $html;
    }
}
