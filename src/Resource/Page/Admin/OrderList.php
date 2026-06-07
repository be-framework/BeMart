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
use MyVendor\BeMart\Be\Final\AdminOrderListFetched;
use MyVendor\BeMart\Be\Input\GetAdminOrderListInput;
use MyVendor\BeMart\Form\AdminOrderSearchForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE goOrderList — 受注一覧 (Wave 7, admin order grid).
 *
 * Safe read. No CSRF (read-only). Admin-only — the Be Final raises
 * {@see UnauthorizedAdminAccessException} when
 * {@see \MyVendor\BeMart\Be\Reason\Service\AdminSession}
 * reports no admin session; we map that to 403. Distinct from the
 * customer-side 401: admin and customer firewalls are parallel (Wave 4
 * decision).
 *
 * Failure mapping:
 *   - SemanticVariableException             → 400 (limit / offset format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *
 * Pagination knobs (`limit` + `offset`) mirror the Wave 6R OrderHistory
 * resource. The original EC-CUBE admin search form additionally supports
 * orderNo / customerName / dateRange / orderStatus / paymentMethod /
 * deliveryMethod filters — those are Phase 2 scope.
 *
 * Hypermedia: links to the per-order detail and to the new-order create
 * affordance (doCreateOrder, deferred — same forward-declaration
 * convention as CustomerList).
 */
class OrderList extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * Wave 7: pagination knobs are admin-form input. Same taint
     * discipline as Wave 5 / Wave 6 admin resources.
     *
     * @psalm-taint-source input $limit
     * @psalm-taint-source input $offset
     */
    #[Alps('goOrderList')]
    #[JsonSchema(schema: 'get-admin-order-list.json', params: 'get-admin-order-list.param.json')]
    #[Link(rel: 'goOrder', href: 'page://self/admin/order', method: 'get')]
    #[Link(rel: 'doCreateOrder', href: 'page://self/admin/order', method: 'post')]
    public function onGet(int $limit = 50, int $offset = 0): static
    {
        try {
            $final = ($this->becoming)(new GetAdminOrderListInput(
                limit: $limit,
                offset: $offset,
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

        assert($final instanceof AdminOrderListFetched);

        $this->code = Code::OK;
        $this->body = [
            'orders' => $final->orders,
            'count' => $final->count,
            'limit' => $final->limit,
            'offset' => $final->offset,
        ];
        // Phase 3: an AdminOrderSearchForm for the HTML list page to
        // render the `multi` keyword box via `{{ searchForm.input(...) }}`.
        // OrderList has no search axis in the Wave 7 slice, so the form is
        // empty (no filters body). JSON contexts ignore this key.
        $this->body['searchForm'] = $this->formFactory->newInstance(AdminOrderSearchForm::class);

        return $this;
    }
}
