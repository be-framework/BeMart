<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\TrackingNumberUpdated;
use MyVendor\BeMart\Be\Input\UpdateTrackingNumberInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doUpdateTrackingNumber — 伝票番号を更新する (Phase 3
 * ALPS-audit remediation).
 *
 *   PUT /admin/order/tracking-number
 *
 * Inline single-row update of an order's shipping tracking number,
 * derived from EC-CUBE's `admin_shipping_update_tracking_number` route.
 * ALPS marks it `idempotent`; PUT is the matching verb.
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (orderNo / trackingNumber)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - OrderNotFoundException                → 404
 */
class TrackingNumber extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $trackingNumber
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    public function onPut(
        string $orderNo,
        string $trackingNumber,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new UpdateTrackingNumberInput(
                orderNo: $orderNo,
                trackingNumber: $trackingNumber,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (OrderNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された注文は見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof TrackingNumberUpdated);

        $this->code = Code::OK;
        $this->body = [
            'orderNo' => $final->orderNo,
            'trackingNumber' => $final->trackingNumber,
            'message' => '伝票番号を更新しました。',
        ];

        return $this;
    }
}
