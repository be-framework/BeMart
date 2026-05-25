<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\CustomerListFetched;
use MyVendor\BeMart\Be\Input\GetCustomerListInput;
use MyVendor\BeMart\Form\AdminCustomerSearchForm;
use Ray\WebFormModule\FormFactory;

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
 * customer-create endpoints. Those are Wave 5+ scope; the link targets
 * exist as resource URIs but the resources themselves are deferred —
 * the BEAR layer is forward-declaring the affordances per the
 * `bear-skills:bear-hypermedia` discipline.
 */
class CustomerList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
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
    #[Link(rel: 'goCustomer', href: 'page://self/admin/customer', method: 'get')]
    #[Link(rel: 'doCreateCustomer', href: 'page://self/admin/customer', method: 'post')]
    public function onGet(
        string|null $nameKeyword = null,
        string|null $emailKeyword = null,
        int $limit = 50,
    ): static {
        try {
            $final = ($this->becoming)(new GetCustomerListInput(
                nameKeyword: $nameKeyword,
                emailKeyword: $emailKeyword,
                limit: $limit,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        assert($final instanceof CustomerListFetched);

        $this->code = Code::OK;
        $this->body = [
            'customers' => $final->customers,
            'count' => $final->count,
            'filters' => $final->filters,
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
