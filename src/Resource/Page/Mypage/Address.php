<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\AddressNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAddressAccessException;
use MyVendor\BeMart\Be\Final\CustomerAddressDeleted;
use MyVendor\BeMart\Be\Final\CustomerAddressUpdated;
use MyVendor\BeMart\Be\Input\DeleteCustomerAddressInput;
use MyVendor\BeMart\Be\Input\UpdateCustomerAddressInput;
use MyVendor\BeMart\Be\Reason\Query\AddressStorageInterface;
use MyVendor\BeMart\Be\Reason\Service\CustomerSession;
use MyVendor\BeMart\Form\AddressForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function array_filter;
use function assert;

/**
 * EC-CUBE 配送先住所 — single-resource endpoint (Pilot 16).
 *
 *   - PUT    → doUpdateCustomerAddress  (edit existing row)
 *   - DELETE → doDeleteCustomerAddress  (remove existing row)
 *
 * addressId is passed in the request payload (BEAR.Sunday's resource
 * client merges body and query into a single argument map; either
 * form reaches `$addressId` here). The collection endpoint
 * `page://self/mypage/address-list` handles GET / POST.
 *
 * AUTHN + AUTHZ are enforced in the Be Final — the customerId is
 * pulled from CustomerSession and compared against the entity's
 * owner. A logged-in customer cannot mutate another customer's
 * address book by guessing addressIds.
 *
 * Failure mapping:
 *   - SemanticVariableException             → 400 (input format)
 *   - UnauthenticatedException              → 401 (no session)
 *   - UnauthorizedAddressAccessException    → 403 (wrong owner)
 *   - AddressNotFoundException              → 404 (unknown id)
 *   - CSRF mismatch (PUT / DELETE)          → 403
 */
class Address extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CustomerSession $session,
        private readonly AddressStorageInterface $addresses,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * EC-CUBE お届け先情報編集 — show the address add/edit form.
     *
     * Pure form-info endpoint: no Be Framework, no domain logic. Maps
     * EC-CUBE's `mypage_delivery_new` (no `addressId`) and
     * `mypage_delivery_edit` (`addressId` given) screens. AUTHN +
     * ownership AUTHZ are enforced here directly (mirrors Withdraw::onGet
     * — a Resource-level guard on a no-domain form page):
     *
     *   - no session                    → 401
     *   - addressId of an unknown row    → 404
     *   - addressId owned by another     → 403
     *
     * Phase 3 — HTML FORM page. The resource builds an {@see AddressForm}
     * (Ray.WebFormModule AbstractForm) and exposes it as `body['form']`.
     * For the edit screen the form is pre-populated from the stored
     * address; for the new screen it is empty. VALIDATION AUTHORITY
     * STAYS WITH the Be Framework Becoming chain (onPost). The JSON
     * contexts ignore `body['form']`.
     *
     * @psalm-taint-source input $addressId
     */
    #[Alps('doUpdateCustomerAddress')]
    #[JsonSchema(schema: 'get-mypage-address.json', params: 'get-mypage-address.param.json')]
    #[Link(rel: 'doCreateCustomerAddress', href: 'page://self/mypage/address-list', method: 'post')]
    #[Link(rel: 'doUpdateCustomerAddress', href: 'page://self/mypage/address', method: 'put')]
    #[Link(rel: 'goCustomerAddressList', href: 'page://self/mypage/address-list')]
    public function onGet(string|null $addressId = null): static
    {
        $customerId = $this->session->customerId;
        if ($customerId === null) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'この操作を行うにはログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AddressForm::class);
        assert($form instanceof AddressForm);

        $address = null;
        if ($addressId !== null) {
            $address = $this->addresses->item($addressId);
            if ($address === null) {
                $this->code = Code::NOT_FOUND;
                $this->body = ['message' => 'Address not found.', 'addressId' => $addressId];

                return $this;
            }

            if ($address->customerId !== $customerId) {
                $this->code = Code::FORBIDDEN;
                $this->body = [
                    'message' => 'この配送先へのアクセス権限がありません。',
                    'addressId' => $addressId,
                ];

                return $this;
            }

            // Pre-populate the edit form with the stored address.
            $form->fillValues(array_filter([
                'name01' => $address->name01,
                'name02' => $address->name02,
                'kana01' => $address->kana01,
                'kana02' => $address->kana02,
                'companyName' => $address->companyName,
                'postalCode' => $address->postalCode,
                'pref' => $address->pref,
                'addr01' => $address->addr01,
                'addr02' => $address->addr02,
                'phoneNumber' => $address->phoneNumber,
            ], static fn (mixed $v): bool => $v !== null));
        }

        $this->code = Code::OK;
        $this->body = [
            'transitionId' => $addressId === null ? 'doCreateCustomerAddress' : 'doUpdateCustomerAddress',
            'addressId' => $addressId,
            'submitTo' => $addressId === null
                ? ['method' => 'POST', 'href' => 'page://self/mypage/address-list']
                : ['method' => 'PUT', 'href' => 'page://self/mypage/address'],
            'csrfToken' => null,
            'form' => $form,
        ];

        return $this;
    }

    /**
     * ALPS `doUpdateCustomerAddress` に対応する PUT 操作。
     * @psalm-taint-source input $addressId
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $companyName
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $phoneNumber
     */
    #[Alps('doUpdateCustomerAddress')]
    #[JsonSchema(schema: 'put-mypage-address.json', params: 'put-mypage-address.param.json')]
    #[Link(rel: 'goCustomerAddressList', href: 'page://self/mypage/address-list')]
    #[CsrfProtected]
    public function onPut(
        string $addressId,
        string|null $name01 = null,
        string|null $name02 = null,
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $companyName = null,
        string|null $postalCode = null,
        int|null $pref = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
        string|null $phoneNumber = null,
    ): static {
        try {
            $final = ($this->becoming)(new UpdateCustomerAddressInput(
                addressId: $addressId,
                name01: $name01,
                name02: $name02,
                kana01: $kana01,
                kana02: $kana02,
                companyName: $companyName,
                postalCode: $postalCode,
                pref: $pref,
                addr01: $addr01,
                addr02: $addr02,
                phoneNumber: $phoneNumber,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
                'addressId' => $addressId,
            ];

            return $this;
        } catch (UnauthenticatedException) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'この操作を行うにはログインが必要です。'];

            return $this;
        } catch (UnauthorizedAddressAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = [
                'message' => 'この配送先へのアクセス権限がありません。',
                'addressId' => $addressId,
            ];

            return $this;
        } catch (AddressNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = [
                'message' => 'Address not found.',
                'addressId' => $addressId,
            ];

            return $this;
        }

        assert($final instanceof CustomerAddressUpdated);

        $this->code = Code::OK;
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
     * ALPS `doUpdateCustomerAddress` に対応する DELETE 操作。
     * @psalm-taint-source input $addressId
     */
    #[Alps('doUpdateCustomerAddress')]
    #[JsonSchema(schema: 'delete-mypage-address.json', params: 'delete-mypage-address.param.json')]
    #[Link(rel: 'goFavoriteList', href: 'page://self/mypage/favorite-list')]
    #[Link(rel: 'goCustomerAddressList', href: 'page://self/mypage/address-list')]
    #[CsrfProtected]
    public function onDelete(string $addressId): static
    {
        try {
            $final = ($this->becoming)(new DeleteCustomerAddressInput(addressId: $addressId));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
                'addressId' => $addressId,
            ];

            return $this;
        } catch (UnauthenticatedException) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'この操作を行うにはログインが必要です。'];

            return $this;
        } catch (UnauthorizedAddressAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = [
                'message' => 'この配送先へのアクセス権限がありません。',
                'addressId' => $addressId,
            ];

            return $this;
        } catch (AddressNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = [
                'message' => 'Address not found.',
                'addressId' => $addressId,
            ];

            return $this;
        }

        assert($final instanceof CustomerAddressDeleted);

        $this->code = Code::OK;
        $this->body = [
            'addressId' => $final->addressId,
            'customerId' => $final->customerId,
        ];

        return $this;
    }
}
