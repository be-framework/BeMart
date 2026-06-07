<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Exception\UnauthorizedOrderAccessException;
use MyVendor\BeMart\Be\Final\MypageHistoryFetched;
use MyVendor\BeMart\Be\Input\GetMypageHistoryInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goMypageHistory — 注文履歴詳細 (Mypage/History).
 *
 * Safe read. No CSRF (read-only). AUTHN + AUTHZ are enforced in the
 * Be layer: the customer can only see their own past orders, and the
 * orderNo→customerId AUTHZ check is sequenced after existence so the
 * 404 vs 403 distinction is preserved (Pilot 12 pattern).
 *
 * Failure mapping:
 *   - SemanticVariableException         → 400 (orderNo malformed)
 *   - UnauthenticatedException          → 401 (no session)
 *   - UnauthorizedOrderAccessException  → 403 (not the order owner)
 *   - OrderNotFoundException            → 404 (no such order)
 */
class History extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `goMypageHistory` に対応する GET 操作。
     * @psalm-taint-source input $orderNo
     */
    #[Alps('goMypageHistory')]
    #[JsonSchema(schema: 'get-mypage-history.json', params: 'get-mypage-history.param.json')]
    #[Link(rel: 'doReorder', href: 'page://self/mypage/reorder', method: 'post')]
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    public function onGet(string $orderNo): static
    {
        try {
            $final = ($this->becoming)(new GetMypageHistoryInput(orderNo: $orderNo));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
                'orderNo' => $orderNo,
            ];

            return $this;
        } catch (UnauthenticatedException) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'この操作を行うにはログインが必要です。'];

            return $this;
        } catch (UnauthorizedOrderAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = [
                'message' => 'この注文へのアクセス権限がありません。',
                'orderNo' => $orderNo,
            ];

            return $this;
        } catch (OrderNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = [
                'message' => 'Order not found.',
                'orderNo' => $orderNo,
            ];

            return $this;
        }

        assert($final instanceof MypageHistoryFetched);

        $this->code = Code::OK;
        $this->body = [
            'orderNo' => $final->orderNo,
            'message' => $final->message,
            'paymentMethod' => $final->paymentMethod,
            'subtotal' => $final->subtotal,
            'deliveryFeeTotal' => $final->deliveryFeeTotal,
            'charge' => $final->charge,
            'discount' => $final->discount,
            'tax' => $final->tax,
            'total' => $final->total,
            'paymentTotal' => $final->paymentTotal,
            'addPoint' => $final->addPoint,
            'usePoint' => $final->usePoint,
            'orderStatus' => $final->orderStatus,
            'orderDate' => $final->orderDate,
            'paymentDate' => $final->paymentDate,
            'shippings' => $final->shippings,
            'mailHistories' => $final->mailHistories,
        ];

        return $this;
    }
}
