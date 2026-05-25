<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Mypage;

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
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

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
 * pulled from SessionInterface and compared against the entity's
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
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
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
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goCustomerAddressList', href: 'page://self/mypage/address-list')]
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
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

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
     * @psalm-taint-source input $addressId
     * @psalm-taint-source input $csrfToken
     */
    #[Link(rel: 'goCustomerAddressList', href: 'page://self/mypage/address-list')]
    public function onDelete(string $addressId, string|null $csrfToken = null): static
    {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

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
