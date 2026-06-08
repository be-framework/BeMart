<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCustomerCreated;
use MyVendor\BeMart\Be\Input\AdminCreateCustomerInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function sprintf;
use function urlencode;

/**
 * EC-CUBE doCreateCustomer — 会員を作成する (管理画面).
 *
 * Admin-side counterpart of Pilot 4's {@see \MyVendor\BeMart\Resource\Page\Entry}.
 * Resource is the HTTP entry point: builds AdminCreateCustomerInput,
 * hands it to Becoming, and projects the resulting AdminCustomerCreated
 * into the response body. The 4 required form fields (email /
 * password / name01 / name02) match `doCreateCustomer.descriptor[]` in
 * alps.json; the 11 optional fields mirror the front-end self-service
 * form so the admin screen can reuse the same field set.
 *
 * ALPS doc: 管理画面から会員を新規作成する。仮会員フラグなしで即時本会員として登録 —
 * the Being fixes customerStatus to 2 (Active) with no provisional path.
 *
 * Failure mapping:
 *   - SemanticVariableException          → 400 (email/password/name format)
 *   - UnauthorizedAdminAccessException   → 403 (no admin session)
 *   - EmailAlreadyRegisteredException    → 409 (email already taken)
 *
 * On success the response is 201 with a `Location` header pointing at
 * the admin Customer detail URL keyed by email — matching the
 * `goCustomer` ALPS transition surface (`#email` is its descriptor).
 */
class CreateCustomer extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * Wave 5: every form field is user-controlled input — same taint
     * discipline as the front-end entry. The admin AUTHZ check lives
     * inside the first Being (AdminCustomerCreating), so this method
     * can stay free of session lookups; we just map the exception.
     *
     * @psalm-taint-source input $email
     * @psalm-taint-source input $password
     * @psalm-taint-source input $name01
     * @psalm-taint-source input $name02
     * @psalm-taint-source input $kana01
     * @psalm-taint-source input $kana02
     * @psalm-taint-source input $companyName
     * @psalm-taint-source input $phoneNumber
     * @psalm-taint-source input $postalCode
     * @psalm-taint-source input $pref
     * @psalm-taint-source input $addr01
     * @psalm-taint-source input $addr02
     * @psalm-taint-source input $birth
     * @psalm-taint-source input $sex
     * @psalm-taint-source input $job
     */
    #[Alps('doCreateCustomer')]
    #[JsonSchema(schema: 'post-admin-create-customer.json', params: 'post-admin-create-customer.param.json')]
    #[Link(rel: 'goCustomer', href: 'page://self/admin/customer', method: 'get')]
    #[CsrfProtected]
    public function onPost(
        string $email,
        string $password,
        string $name01,
        string $name02,
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $companyName = null,
        string|null $phoneNumber = null,
        string|null $postalCode = null,
        int|null $pref = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
        string|null $birth = null,
        int|null $sex = null,
        int|null $job = null,
            string|null $csrfToken = null,
    ): static {
        $final = ($this->becoming)(new AdminCreateCustomerInput(
            email: $email,
            password: $password,
            name01: $name01,
            name02: $name02,
            kana01: $kana01,
            kana02: $kana02,
            companyName: $companyName,
            phoneNumber: $phoneNumber,
            postalCode: $postalCode,
            pref: $pref,
            addr01: $addr01,
            addr02: $addr02,
            birth: $birth,
            sex: $sex,
            job: $job,
        ));

        assert($final instanceof AdminCustomerCreated);

        $this->code = Code::CREATED;
        // goCustomer takes #email as its sole descriptor; the admin
        // Customer detail URL is keyed on email accordingly.
        $this->headers['Location'] = sprintf('/admin/customer?email=%s', urlencode($final->email));
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'name01' => $final->name01,
            'name02' => $final->name02,
            'initialPoint' => $final->initialPoint,
            'customerStatus' => $final->customerStatus,
        ];

        return $this;
    }
}
