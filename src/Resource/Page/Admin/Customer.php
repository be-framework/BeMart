<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use Ray\Csrf\Attribute\CsrfToken;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\EmailAlreadyRegisteredException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCustomerFetched;
use MyVendor\BeMart\Be\Final\AdminCustomerUpdated;
use MyVendor\BeMart\Be\Input\AdminUpdateCustomerInput;
use MyVendor\BeMart\Be\Input\GetAdminCustomerInput;
use MyVendor\BeMart\Form\AdminCustomerForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function filter_var;
use function sprintf;
use function urlencode;

use const FILTER_VALIDATE_EMAIL;

/**
 * EC-CUBE goCustomer — 会員詳細を見る（管理画面）.
 *
 * Safe read. No CSRF (read-only). Admin-only — the Be Final raises
 * UnauthorizedAdminAccessException when the admin session is empty,
 * which this resource maps to 403. Aggregates full profile + complete
 * order history + favorites list into a flat admin detail projection.
 *
 * Failure mapping (cross-firewall AUTHZ → existence ladder):
 *   - SemanticVariableException            → 400 (email format invalid)
 *   - UnauthorizedAdminAccessException     → 403 (no admin session)
 *   - CustomerNotFoundException            → 404 (no such email)
 *
 * The 403-before-404 ordering matches the Be Final's check sequence —
 * an admin-anonymous client learns NOTHING about which emails resolve
 * (same anti-enumeration discipline as the customer-side Pilot 8 /
 * Pilot 12 AUTHN-first ladders).
 *
 * Unlike the customer's own goMypage, this surface is the FULL profile
 * (birth, sex, job, full address, point balance, registrationDate
 * analogue), FULL order history (capped at 50 with derived totalSpent),
 * and FULL favorites list (not just the count). The admin back-office
 * needs the richer projection — drill-downs into individual orders /
 * favorites are deferred to dedicated admin endpoints.
 *
 * Mirrors {@see Login} / {@see Logout} for the admin firewall —
 * distinct namespace under `Page\Admin\` (URI prefix
 * `page://self/admin/...`). Coexists with a potential future
 * `Page\Admin\Customer\` sibling directory: PHP allows a file and a
 * sibling directory of the same name to share a namespace prefix
 * (same as `Resource\Page\Mypage.php` + `Resource\Page\Mypage\`).
 */
class Customer extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
        private readonly MutationResponseInterface $mutationResponse,
    ) {
    }

    /**
     * Wave 5: the email comes from the admin UI (typed input or query
     * string), so it is user-controlled — same taint discipline as the
     * customer-side LoginResource.
     *
     * @psalm-taint-source input $email
     * @psalm-taint-source input $customerId
     * @psalm-taint-source input $id
     */
    #[Alps('goCustomer')]
    #[JsonSchema(schema: 'get-admin-customer.json', params: 'get-admin-customer.param.json')]
    #[Link(rel: 'goCustomerList', href: 'page://self/admin/customer-list')]
    #[Link(rel: 'doUpdateCustomerProfile', href: 'page://self/admin/customer', method: 'post')]
    public function onGet(
        string|null $email = null,
        string|null $customerId = null,
        string|null $id = null,
    ): static
    {
        $customerId ??= $id;
        $selector = $customerId !== null && $customerId !== '' ? $customerId : $email;
        $selectorType = $customerId !== null && $customerId !== '' ? 'customerId' : 'email';
        if ($selector === null || $selector === '') {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => '会員IDまたはメールアドレスを指定してください。'];

            return $this;
        }

        if ($selectorType === 'email' && filter_var($selector, FILTER_VALIDATE_EMAIL) === false) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => 'メールアドレスの形式が不正です。'];

            return $this;
        }

        $final = ($this->becoming)(new GetAdminCustomerInput(
            selector: $selector,
            selectorType: $selectorType,
        ));

        assert($final instanceof AdminCustomerFetched);

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
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
            'birth' => $final->birth,
            'sex' => $final->sex,
            'job' => $final->job,
            'customerStatus' => $final->customerStatus,
            'initialPoint' => $final->initialPoint,
            'orders' => $final->orders,
            'orderCount' => $final->orderCount,
            'totalSpent' => $final->totalSpent,
            'favorites' => $final->favorites,
            'favoriteCount' => $final->favoriteCount,
        ];
        // Phase 3: an AdminCustomerForm pre-filled with the persisted
        // profile, for the HTML edit page (Customer.html.twig) to render
        // via `{{ form.input(...) }}`. JSON contexts ignore `body.form`;
        // the resource tests assert key-wise on body and are unaffected.
        $form = $this->formFactory->newInstance(AdminCustomerForm::class);
        assert($form instanceof AdminCustomerForm);
        $form->fillValues($this->body);
        $this->body['form'] = $form;

        return $this;
    }

    /**
     * EC-CUBE doUpdateCustomerProfile — 会員情報を更新する（管理画面）.
     *
     * Admin-side edit of an EXISTING customer. The `customerId` comes
     * from the form (the admin_customer_edit `{id}`) — a foreign id —
     * so the admin AUTHZ check lives inside the first Being
     * (AdminCustomerUpdating, which throws before the load). This method
     * stays free of session lookups and just maps the exceptions.
     *
     * Failure mapping (matching the onGet ladder + the create sibling):
     *   - SemanticVariableException          → 400 (email/name format)
     *   - UnauthorizedAdminAccessException   → 403 (no admin session)
     *   - CustomerNotFoundException          → 404 (unknown customerId)
     *   - EmailAlreadyRegisteredException    → 409 (email already taken)
     *
     * On success: 200 with a `Location` header pointing back at the
     * admin Customer detail URL keyed by the opaque customerId
     * (POST-redirect-GET) — never by email, so no PII enters the URL.
     *
     * @psalm-taint-source input $customerId
     * @psalm-taint-source input $email
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
     * @psalm-taint-source input $password
     */
    #[Alps('doUpdateCustomerProfile')]
    #[JsonSchema(schema: 'post-admin-update-customer.json', params: 'post-admin-update-customer.param.json')]
    #[Link(rel: 'goCustomer', href: 'page://self/admin/customer', method: 'get')]
    #[CsrfToken]
    public function onPost(
        string $customerId,
        string $email,
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
        string|null $password = null,
    ): static {
        $final = ($this->becoming)(new AdminUpdateCustomerInput(
            customerId: $customerId,
            email: $email,
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
            password: $password,
        ));

        assert($final instanceof AdminCustomerUpdated);

        ($this->mutationResponse)(
            $this,
            Code::OK,
            sprintf('/admin/customer?customerId=%s', urlencode($final->customerId)),
        );
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'name01' => $final->name01,
            'name02' => $final->name02,
        ];

        return $this;
    }
}
