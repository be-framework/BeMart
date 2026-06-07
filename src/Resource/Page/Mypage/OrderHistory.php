<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\OrderHistoryFetched;
use MyVendor\BeMart\Be\Input\GetOrderHistoryInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goOrderHistory — 注文履歴一覧 (Mypage/OrderHistory).
 *
 * Safe read. No CSRF (read-only). AUTHN is enforced in the Be layer: the
 * customer's full order history is surfaced from {@see CustomerSession}'s
 * customerId, so request-parameter tampering cannot widen the scope to
 * another customer's orders.
 *
 * Distinct from `page://self/mypage` (the dashboard, which only carries
 * the most recent 5 orders for the summary panel): this resource is the
 * unbounded view, paged by `historyLimit` + `offset`.
 *
 * Failure mapping:
 *   - SemanticVariableException → 400 (limit / offset out of range)
 *   - UnauthenticatedException  → 401 (no / stale session)
 */
class OrderHistory extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /** ALPS `goOrderHistory` に対応する GET 操作。 */
    #[Alps('goOrderHistory')]
    #[JsonSchema(schema: 'get-mypage-order-history.json', params: 'get-mypage-order-history.param.json')]
    #[Link(rel: 'goMypageHistory', href: 'page://self/mypage/history', method: 'get')]
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    public function onGet(int $historyLimit = 50, int $offset = 0): static
    {
        try {
            $final = ($this->becoming)(new GetOrderHistoryInput(
                historyLimit: $historyLimit,
                offset: $offset,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthenticatedException) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'この操作を行うにはログインが必要です。'];

            return $this;
        }

        assert($final instanceof OrderHistoryFetched);

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'orders' => $final->orders,
            'orderCount' => $final->orderCount,
            'limit' => $final->limit,
            'offset' => $final->offset,
        ];

        return $this;
    }
}
