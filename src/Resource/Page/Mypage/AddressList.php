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
use MyVendor\BeMart\Be\Exception\UnauthenticatedException;
use MyVendor\BeMart\Be\Final\CustomerAddressCreated;
use MyVendor\BeMart\Be\Final\CustomerAddressListFetched;
use MyVendor\BeMart\Be\Input\CreateCustomerAddressInput;
use MyVendor\BeMart\Be\Input\GetCustomerAddressListInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function getenv;
use function str_contains;

/**
 * EC-CUBE 配送先住所一覧 — collection endpoint (Pilot 16).
 *
 * Two responsibilities at one URI per BEAR.Sunday REST convention:
 *
 *   - GET  → goCustomerAddressList       (list the book — safe read)
 *   - POST → doCreateCustomerAddress     (add a new row)
 *
 * Single-resource operations (PUT / DELETE) live at
 * `page://self/mypage/address` (see Address resource).
 *
 * Failure mapping:
 *   - SemanticVariableException → 400 (parameter format invalid)
 *   - UnauthenticatedException  → 401 (no / stale session)
 *
 * GET is safe and skips CSRF; POST is unsafe and validates CSRF.
 * customerId is NEVER taken from the request body — the Be Final
 * pulls it from CustomerSession (Pilot 5 F-2 / Pilot 8 lesson).
 */
class AddressList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /** ALPS `goCustomerAddressList` に対応する GET 操作。 */
    #[Alps('goCustomerAddressList')]
    #[JsonSchema(schema: 'get-mypage-address-list.json')]
    #[Link(rel: 'doCreateCustomerAddress', href: 'page://self/mypage/address-list', method: 'post')]
    #[Link(rel: 'doUpdateCustomerAddress', href: 'page://self/mypage/address', method: 'put')]
    #[Link(rel: 'doDeleteCustomerAddress', href: 'page://self/mypage/address', method: 'delete')]
    #[Link(rel: 'goMypage', href: 'page://self/mypage')]
    public function onGet(): static
    {
        $final = ($this->becoming)(new GetCustomerAddressListInput());

        assert($final instanceof CustomerAddressListFetched);

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'count' => $final->count,
            'addresses' => $final->addresses,
        ];

        return $this;
    }

    /**
     * ALPS `doCreateCustomerAddress` に対応する POST 操作。
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
    #[Alps('doCreateCustomerAddress')]
    #[JsonSchema(schema: 'post-mypage-address-list.json', params: 'post-mypage-address-list.param.json')]
    #[Link(rel: 'goCustomerAddressList', href: 'page://self/mypage/address-list')]
    #[CsrfProtected]
    public function onPost(
        string $name01,
        string $name02,
        string $postalCode,
        int $pref,
        string $addr01,
        string $addr02,
        string $phoneNumber,
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $companyName = null,
    ): static {
        $final = ($this->becoming)(new CreateCustomerAddressInput(
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

        assert($final instanceof CustomerAddressCreated);

        $this->code = Code::CREATED;
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

        return $this->redirectToAddressListOnHtmlSuccess();
    }

    private function redirectToAddressListOnHtmlSuccess(): static
    {
        if ($this->code < 400 && str_contains((string) getenv('APP_CONTEXT'), 'html')) {
            $this->code = Code::SEE_OTHER;
            $this->headers['Location'] = '/mypage/address-list';
        }

        return $this;
    }
}
