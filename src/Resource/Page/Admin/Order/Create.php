<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\ApiDoc\Annotation\Alps;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminOrderCreated;
use MyVendor\BeMart\Be\Input\AdminCreateOrderInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;
use function preg_match;

/**
 * EC-CUBE doCreateOrder — 受注を手動作成する (Wave 9η,
 * **Phase 2 simplification**).
 *
 *   POST /admin/order/create
 *
 * Admin-created orders bypass Cart, PaymentMethod::verify(), and the
 * customer-side checkout entirely (EC-CUBE supports this for phone /
 * FAX orders entered by back-office staff). The admin posts the
 * purchased line items (`orderItems`) plus the delivery / charge /
 * discount columns; {@see AdminOrderCreated} recomputes subtotal / tax /
 * total via the shared PurchaseFlow and persists the dtb_order_item
 * snapshot. The orderNo is allocated server-side via the existing
 * {@see \MyVendor\BeMart\Be\Reason\Provider\OrderNoProvider} — admins
 * cannot inject a chosen orderNo.
 *
 * Failure mapping:
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (field formats)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 */
class Create extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * ALPS `doCreateOrder` に対応する POST 操作。
     * @param list<array{productCode: string, productName: string, unitPrice: int, quantity: int}> $orderItems
     *
     * @psalm-taint-source input $customerId
     * @psalm-taint-source input $paymentMethodId
     * @psalm-taint-source input $orderItems
     * @psalm-taint-source input $deliveryFeeTotal
     * @psalm-taint-source input $charge
     * @psalm-taint-source input $discount
     */
    #[Alps('doCreateOrder')]
    #[JsonSchema(schema: 'post-admin-order-create.json', params: 'post-admin-order-create.param.json')]
    #[Link(rel: 'goOrderList', href: 'page://self/admin/order-list')]
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    #[CsrfToken]
    public function onPost(
        string $customerId,
        int|string $paymentMethodId,
        array $orderItems,
        int|string $deliveryFeeTotal = 0,
        int|string $charge = 0,
        int|string $discount = 0,
    ): static {
        $paymentMethodId = self::intStringToInt($paymentMethodId);
        $deliveryFeeTotal = self::intStringToInt($deliveryFeeTotal);
        $charge = self::intStringToInt($charge);
        $discount = self::intStringToInt($discount);
        $orderItems = self::normalizeOrderItems($orderItems);

        if ($orderItems === null) {
            return $this->badRequest('orderItems');
        }

        if (! is_int($paymentMethodId)) {
            return $this->badRequest('paymentMethodId');
        }

        if (! is_int($deliveryFeeTotal)) {
            return $this->badRequest('deliveryFeeTotal');
        }

        if (! is_int($charge)) {
            return $this->badRequest('charge');
        }

        if (! is_int($discount)) {
            return $this->badRequest('discount');
        }

        $final = ($this->becoming)(new AdminCreateOrderInput(
            customerId: $customerId,
            paymentMethodId: $paymentMethodId,
            orderItems: $orderItems,
            deliveryFeeTotal: $deliveryFeeTotal,
            charge: $charge,
            discount: $discount,
        ));

        assert($final instanceof AdminOrderCreated);

        ($this->mutationResponse)($this, Code::CREATED, '/admin/order?orderNo=' . $final->orderNo);
        $this->body = [
            'orderNo' => $final->orderNo,
            'customerId' => $final->customerId,
            'paymentMethodId' => $final->paymentMethodId,
            'subtotal' => $final->subtotal,
            'deliveryFeeTotal' => $final->deliveryFeeTotal,
            'charge' => $final->charge,
            'discount' => $final->discount,
            'tax' => $final->tax,
            'total' => $final->total,
            'paymentTotal' => $final->paymentTotal,
            'addPoint' => $final->addPoint,
            'itemCount' => $final->itemCount,
            'orderStatus' => $final->orderStatus,
            'orderDate' => $final->orderDate,
        ];

        return $this;
    }

    /**
     * HTTP form fields arrive as strings inside nested arrays. Normalize the
     * EC-CUBE form shape before passing a typed item list into the Be input.
     *
     * @param array<int, mixed> $orderItems
     * @return list<array{productCode: string, productName: string, unitPrice: int, quantity: int}>|null
     */
    private static function normalizeOrderItems(array $orderItems): array|null
    {
        $normalized = [];
        foreach ($orderItems as $item) {
            if (! is_array($item)) {
                return null;
            }

            if (isset($item['unitPrice'])) {
                $item['unitPrice'] = self::intStringToInt($item['unitPrice']);
            }

            if (isset($item['quantity'])) {
                $item['quantity'] = self::intStringToInt($item['quantity']);
            }

            if (
                ! array_key_exists('productCode', $item)
                || ! array_key_exists('productName', $item)
                || ! array_key_exists('unitPrice', $item)
                || ! array_key_exists('quantity', $item)
                || ! is_string($item['productCode'])
                || ! is_string($item['productName'])
                || ! is_int($item['unitPrice'])
                || ! is_int($item['quantity'])
            ) {
                return null;
            }

            $normalized[] = [
                'productCode' => $item['productCode'],
                'productName' => $item['productName'],
                'unitPrice' => $item['unitPrice'],
                'quantity' => $item['quantity'],
            ];
        }

        return $normalized;
    }

    private static function intStringToInt(mixed $value): mixed
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        return $value;
    }

    private function badRequest(string $field): static
    {
        $this->code = Code::BAD_REQUEST;
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        $this->body = ['code' => Code::BAD_REQUEST, 'message' => $field];

        return $this;
    }
}
