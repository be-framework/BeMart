<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\ApiDoc\Annotation\Alps;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\InsufficientStockException;
use MyVendor\BeMart\Be\Exception\PaymentDeclinedException;
use MyVendor\BeMart\Be\Exception\PreOrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedPreOrderAccessException;
use MyVendor\BeMart\Be\Final\CheckoutCompleted;
use MyVendor\BeMart\Be\Input\CheckoutInput;
use BEAR\Resource\Annotation\JsonSchema;

use function array_key_exists;
use function assert;

/**
 * EC-CUBE doCheckout —注文確定 (Shopping/Checkout).
 *
 * Resource is the HTTP entry point: builds CheckoutInput, hands it to
 * Becoming, and projects the resulting CheckoutCompleted into the
 * ShoppingComplete response body. Pilot 5 deliberately maps Reason-thrown
 * DomainExceptions to HTTP codes (ShoppingError 422 / 404) rather than
 * routing through a Branching Final — Branching itself was already covered
 * by Pilot 3, so we keep the failure path simple.
 *
 * Failure mapping (per `be/docs/pilot5/alps-analyze.md` §例外フロー):
 *   - PreOrderNotFoundException           → 404 (the pre-order never existed)
 *   - UnauthorizedPreOrderAccessException → 403 (not the owner; Pilot 5 F-1)
 *   - InsufficientStockException          → 422 (stock cannot fulfill the order)
 *   - PaymentDeclinedException            → 422 (gateway refused the charge)
 *   - SemanticVariableException           → 400 (preOrderId malformed)
 *
 * Note: paymentMethodId is intentionally NOT accepted here. It is sourced
 * from the persisted OrderEntity inside CheckoutSettled to prevent
 * mass-assignment tampering (Pilot 5 F-2).
 */
class Checkout extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * Phase B Slice 9: the domain parameter arrives from the HTTP request body.
     * `$preOrderId` is a 40-hex-char id that PreOrderId Semantic
     * format-validates. The CSRF boundary token is enforced declaratively by
     * the CsrfToken attribute.
     *
     * @psalm-taint-source input $preOrderId
     */
    #[Alps('doCheckout')]
    #[JsonSchema(schema: 'post-shopping-checkout.json', params: 'post-shopping-checkout.param.json')]
    #[Link(rel: 'goTop', href: 'page://self/')]
    #[Link(rel: 'goCart', href: 'page://self/cart')]
    #[CsrfToken]
    public function onPost(string $preOrderId): static
    {
        $final = ($this->becoming)(new CheckoutInput(
            preOrderId: $preOrderId,
        ));

        assert($final instanceof CheckoutCompleted);

        $browserForm = array_key_exists('mode', $this->uri->query);
        $this->code = $browserForm ? Code::SEE_OTHER : Code::CREATED;
        $this->headers['Location'] = '/shopping/complete?orderNo=' . $final->orderNo;
        $this->body = [
            'orderNo' => $final->orderNo,
            'completeMessage' => $final->completeMessage,
            'customerId' => $final->customerId,
            'total' => $final->total,
            'paymentTotal' => $final->paymentTotal,
            'addPoint' => $final->addPoint,
            'orderStatus' => $final->orderStatus,
            'orderDate' => $final->orderDate,
            'paymentDate' => $final->paymentDate,
        ];

        return $this;
    }
}
