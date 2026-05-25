<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Support\Resource;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\PhpClassInvoker;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Exception\CsrfTokenInvalidException;
use Override;

/**
 * Captures the original Resource request query/body while invoking on*().
 *
 * The context is a stack because nested Resource requests can happen inside a
 * single synchronous PHP request. AppModule binds it as a singleton, which is
 * safe for the current per-request PHP process model; long-lived concurrent
 * workers must replace this binding with a request-/fiber-local context.
 */
final readonly class RequestQueryCapturingInvoker implements InvokerInterface
{
    public function __construct(
        private PhpClassInvoker $classInvoker,
        private RequestQueryContext $queryContext,
    ) {
    }

    #[Override]
    public function invoke(AbstractRequest $request): ResourceObject
    {
        $this->queryContext->push($request->query);
        try {
            return $request->resourceObject->_invokeRequest($this->classInvoker, $request);
        } catch (CsrfTokenInvalidException $e) {
            $request->resourceObject->code = $e->getCode();
            $request->resourceObject->body = ['message' => $e->getMessage()];

            return $request->resourceObject;
        } finally {
            $this->queryContext->pop();
        }
    }
}
