<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCustomerFetched;
use MyVendor\BeMart\Be\Input\GetAdminCustomerInput;
use MyVendor\BeMart\Form\AdminCustomerForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;
use function filter_var;

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

        try {
            $final = ($this->becoming)(new GetAdminCustomerInput(
                selector: $selector,
                selectorType: $selectorType,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
            ];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (CustomerNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された会員は見つかりませんでした。'];

            return $this;
        }

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
}
