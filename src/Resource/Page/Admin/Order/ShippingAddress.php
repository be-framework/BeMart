<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Order;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\AddressNotFoundException;
use MyVendor\BeMart\Be\Exception\OrderNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminShippingAddressSelected;
use MyVendor\BeMart\Be\Final\AdminShippingAddressUpdated;
use MyVendor\BeMart\Be\Input\AdminSelectShippingAddressInput;
use MyVendor\BeMart\Be\Input\AdminUpdateShippingAddressInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doSelectShippingAddress + doUpdateShippingAddress (admin
 * side) — 受注の配送先を操作する (Wave 9η).
 *
 *   POST /admin/order/shipping-address → doSelectShippingAddress
 *   PUT  /admin/order/shipping-address → doUpdateShippingAddress
 *
 * Why a single resource for both transitions: they target the same
 * underlying state (the order's shipping-address row). POST means
 * "pick from the address book" (lookup by addressId, copy fields);
 * PUT means "overwrite the row with explicit fields". The collapse
 * mirrors the Wave 6R address-book resource which carries POST /
 * GET / PUT / DELETE on the same shape.
 *
 * Note on actor scope: ALPS marks the two transitions `actor-customer`
 * (checkout flow). The Wave 9η iteration adds an admin-side entry
 * point because the back-office order-edit screen needs to manage the
 * shipping target after the order is finalized. The customer-side
 * renderers (Wave 3H static forms) still exist at
 * `page://self/shopping/shipping{,-edit}`.
 *
 * Failure mapping (both methods):
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400
 *   - UnauthorizedAdminAccessException      → 403
 *   - OrderNotFoundException                → 404
 *   - AddressNotFoundException (POST only)  → 404
 */
class ShippingAddress extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * doSelectShippingAddress — pick an address-book row for the order.
     *
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $addressId
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    #[Link(rel: 'doUpdateShippingAddress', href: 'page://self/admin/order/shipping-address', method: 'put')]
    public function onPost(
        string $orderNo,
        string $addressId,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AdminSelectShippingAddressInput(
                orderNo: $orderNo,
                addressId: $addressId,
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
        } catch (AddressNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された住所は見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof AdminShippingAddressSelected);

        return $this->respond($final);
    }

    /**
     * doUpdateShippingAddress — overwrite the order's shipping fields.
     *
     * @psalm-taint-source input $orderNo
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    #[Link(rel: 'doSelectShippingAddress', href: 'page://self/admin/order/shipping-address', method: 'post')]
    public function onPut(
        string $orderNo,
        string $name01,
        string $name02,
        string $postalCode,
        int $pref,
        string $addr01,
        string $addr02,
        string $phoneNumber,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new AdminUpdateShippingAddressInput(
                orderNo: $orderNo,
                name01: $name01,
                name02: $name02,
                postalCode: $postalCode,
                pref: $pref,
                addr01: $addr01,
                addr02: $addr02,
                phoneNumber: $phoneNumber,
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

        assert($final instanceof AdminShippingAddressUpdated);

        return $this->respond($final);
    }

    private function respond(AdminShippingAddressSelected|AdminShippingAddressUpdated $final): static
    {
        $this->code = Code::OK;
        $this->body = [
            'orderNo' => $final->orderNo,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'postalCode' => $final->postalCode,
            'pref' => $final->pref,
            'addr01' => $final->addr01,
            'addr02' => $final->addr02,
            'phoneNumber' => $final->phoneNumber,
        ];

        return $this;
    }
}
