<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use MyVendor\BeMart\Be\Final\AdminCustomerDeliveryAddressCreated;
use MyVendor\BeMart\Be\Final\AdminCustomerDeliveryAddressDeleted;
use MyVendor\BeMart\Be\Final\AdminCustomerDeliveryAddressUpdated;
use MyVendor\BeMart\Be\Input\AdminCreateCustomerDeliveryAddressInput;
use MyVendor\BeMart\Be\Input\AdminDeleteCustomerDeliveryAddressInput;
use MyVendor\BeMart\Be\Input\AdminUpdateCustomerDeliveryAddressInput;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Form\AdminCustomerDeliveryForm;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Ray\Csrf\Attribute\CsrfToken;
use Ray\WebFormModule\FormFactory;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE お届け先編集 — Customer Tier-2.
 *
 * Renders + persists the admin customer address-book entry editor
 * (`admin/Customer/delivery_edit.twig`):
 *
 *   - GET    → goCustomerDeliveryEdit          (render the edit form)
 *   - POST   → doCreateCustomerDeliveryAddress  (addressId empty → create)
 *            / doUpdateCustomerDeliveryAddress  (addressId present → update)
 *   - DELETE → doDeleteCustomerDeliveryAddress  (drop one address row)
 *
 * Unlike the storefront Mypage flow, the admin acts on a customer keyed by
 * the route-param `customerId` (the admin firewall has no CustomerSession),
 * so the write transitions land on admin-specific Be Inputs/Finals that
 * carry the target customerId explicitly and guard it with the AdminSession
 * (403 when absent — checked FIRST). Writes redirect back to the member
 * edit page (`/admin/customer?customerId=...`), mirroring EC-CUBE's
 * CustomerDeliveryEditController redirect.
 *
 * Admin-only — the AUTHZ guard rejects an anonymous admin with 403,
 * matching the sibling Setting/System Tier-2 renderers ({@see System},
 * {@see Security}, {@see TwoFactorAuthEdit}).
 */
class CustomerDeliveryEdit extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly FormFactory $formFactory,
        private readonly BecomingInterface $becoming,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * The customer id comes from the admin UI (route param), so it is
     * user-controlled — same taint discipline as the sibling
     * {@see Customer} resource.
     *
     * @psalm-taint-source input $customerId
     */
    #[Alps('goCustomerDeliveryEdit')]
    #[JsonSchema(schema: 'get-admin-customer-delivery-edit.json', params: 'get-admin-customer-delivery-edit.param.json')]
    #[Link(rel: 'goCustomerList', href: 'page://self/admin/customer-list')]
    #[Link(rel: 'doCreateCustomerDeliveryAddress', href: 'page://self/admin/customer-delivery-edit', method: 'post')]
    #[Link(rel: 'doUpdateCustomerDeliveryAddress', href: 'page://self/admin/customer-delivery-edit', method: 'post')]
    #[Link(rel: 'doDeleteCustomerDeliveryAddress', href: 'page://self/admin/customer-delivery-edit', method: 'delete')]
    public function onGet(string $customerId = '', string $id = ''): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        if ($customerId === '' && $id !== '') {
            $customerId = $id;
        }

        $form = $this->formFactory->newInstance(AdminCustomerDeliveryForm::class);
        assert($form instanceof AdminCustomerDeliveryForm);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'customerId' => $customerId,
        ];

        return $this;
    }

    /**
     * Persist a customer delivery address. Empty `addressId` creates a new
     * row (doCreateCustomerDeliveryAddress); a present `addressId` updates
     * the existing row in place (doUpdateCustomerDeliveryAddress). The Be
     * Final guards the AdminSession (403 first) then verifies the target
     * customer / address ownership before writing.
     *
     * @psalm-taint-source input $customerId
     * @psalm-taint-source input $addressId
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $companyName
     */
    #[Alps('doUpdateCustomerDeliveryAddress')]
    #[JsonSchema(schema: 'post-admin-customer-delivery-edit.json', params: 'post-admin-customer-delivery-edit.param.json')]
    #[Link(rel: 'goCustomer', href: 'page://self/admin/customer')]
    #[Link(rel: 'goCustomerList', href: 'page://self/admin/customer-list')]
    #[CsrfToken]
    public function onPost(
        string $customerId,
        string $name01,
        string $name02,
        string $postalCode,
        int $pref,
        string $addr01,
        string $addr02,
        string $phoneNumber,
        string $addressId = '',
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $companyName = null,
    ): static {
        if ($addressId === '') {
            $final = ($this->becoming)(new AdminCreateCustomerDeliveryAddressInput(
                customerId: $customerId,
                name01: $name01,
                name02: $name02,
                postalCode: $postalCode,
                pref: $pref,
                addr01: $addr01,
                addr02: $addr02,
                phoneNumber: $phoneNumber,
                kana01: $kana01,
                kana02: $kana02,
                companyName: $companyName,
            ));

            assert($final instanceof AdminCustomerDeliveryAddressCreated);
            $code = Code::CREATED;
        } else {
            $final = ($this->becoming)(new AdminUpdateCustomerDeliveryAddressInput(
                customerId: $customerId,
                addressId: $addressId,
                name01: $name01,
                name02: $name02,
                postalCode: $postalCode,
                pref: $pref,
                addr01: $addr01,
                addr02: $addr02,
                phoneNumber: $phoneNumber,
                kana01: $kana01,
                kana02: $kana02,
                companyName: $companyName,
            ));

            assert($final instanceof AdminCustomerDeliveryAddressUpdated);
            $code = Code::OK;
        }

        ($this->mutationResponse)($this, $code, sprintf('/admin/customer?customerId=%s', urlencode($customerId)));
        $this->body = [
            'addressId' => $final->addressId,
            'customerId' => $final->customerId,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'kana01' => $final->kana01,
            'kana02' => $final->kana02,
            'companyName' => $final->companyName,
            'phoneNumber' => $final->phoneNumber,
            'postalCode' => $final->postalCode,
            'pref' => $final->pref,
            'addr01' => $final->addr01,
            'addr02' => $final->addr02,
        ];

        return $this;
    }

    /**
     * Remove one customer delivery address. The Be Final guards the
     * AdminSession (403 first) then verifies the address is owned by the
     * route-param customer before deleting.
     *
     * @psalm-taint-source input $customerId
     * @psalm-taint-source input $addressId
     */
    #[Alps('doDeleteCustomerDeliveryAddress')]
    #[JsonSchema(schema: 'delete-admin-customer-delivery-edit.json', params: 'delete-admin-customer-delivery-edit.param.json')]
    #[Link(rel: 'goCustomer', href: 'page://self/admin/customer')]
    #[Link(rel: 'goCustomerList', href: 'page://self/admin/customer-list')]
    #[CsrfToken]
    public function onDelete(string $customerId, string $addressId): static
    {
        $final = ($this->becoming)(new AdminDeleteCustomerDeliveryAddressInput(
            customerId: $customerId,
            addressId: $addressId,
        ));

        assert($final instanceof AdminCustomerDeliveryAddressDeleted);

        ($this->mutationResponse)($this, Code::OK, sprintf('/admin/customer?customerId=%s', urlencode($customerId)));
        $this->body = [
            'addressId' => $final->addressId,
            'customerId' => $final->customerId,
        ];

        return $this;
    }
}
