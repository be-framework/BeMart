<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\CustomerListFetched;
use MyVendor\BeMart\Be\Input\GetCustomerListInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfToken;
use MyVendor\BeMart\Form\AdminCustomerSearchForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goCustomerList — 会員一覧 (Wave 5, admin filter search).
 *
 * Safe read. No CSRF (read-only). Admin-only — the Be Final raises
 * UnauthorizedAdminAccessException when AdminSession reports
 * no admin session, which we map to 403. Distinct from customer-side
 * 401 (Unauthenticated): admin and customer firewalls are parallel and
 * a logged-in customer is NOT logged-in-as-admin (Wave 4 decision).
 *
 * Failure mapping:
 *   - SemanticVariableException             → 400 (filter format invalid)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *
 * Filter scope (Wave 5 first iteration):
 *   - nameKeyword  — substring on name01/name02/companyName
 *   - emailKeyword — substring on email
 *   - limit        — caps the result set (default 50)
 *   Phase 2 will add phoneNumber, dateRange, purchaseAmount filters.
 *
 * Hypermedia: links to the per-customer admin detail and the admin
 * customer actions that are available from the list surface.
 */
class CustomerList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfToken $csrf,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * Wave 5: filter fields are admin-form input — taint discipline
     * mirrors the Wave 4 admin login.
     *
     * @psalm-taint-source input $nameKeyword
     * @psalm-taint-source input $emailKeyword
     * @psalm-taint-source input $limit
     */
    #[Alps('goCustomerList')]
    #[JsonSchema(schema: 'get-admin-customer-list.json', params: 'get-admin-customer-list.param.json')]
    #[Link(rel: 'goCustomer', href: 'page://self/admin/customer', method: 'get')]
    #[Link(rel: 'doCreateCustomer', href: 'page://self/admin/create-customer', method: 'post')]
    #[Link(rel: 'doDeleteCustomer', href: 'page://self/admin/delete-customer', method: 'post')]
    #[Link(rel: 'doResendActivationMail', href: 'page://self/admin/customer/resend-activation-mail', method: 'post')]
    public function onGet(
        string|null $nameKeyword = null,
        string|null $emailKeyword = null,
        int $limit = 50,
    ): static {
        $final = ($this->becoming)(new GetCustomerListInput(
            nameKeyword: $nameKeyword,
            emailKeyword: $emailKeyword,
            limit: $limit,
        ));

        assert($final instanceof CustomerListFetched);

        $this->code = Code::OK;
        $this->body = [
            'customers' => $final->customers,
            'count' => $final->count,
            'filters' => $final->filters,
            'csrfToken' => $this->csrf->token,
        ];
        // Phase 3: an AdminCustomerSearchForm for the HTML list page to
        // render the keyword box via `{{ searchForm.input(...) }}`,
        // re-filled with the active filter. JSON contexts ignore it.
        $searchForm = $this->formFactory->newInstance(AdminCustomerSearchForm::class);
        assert($searchForm instanceof AdminCustomerSearchForm);
        $searchForm->fillFilters($final->filters);
        $this->body['searchForm'] = $searchForm;

        return $this;
    }
}
