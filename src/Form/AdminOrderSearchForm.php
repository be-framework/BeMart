<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 受注検索フォーム（管理画面 受注一覧）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/SearchOrderType` + the
 * `admin/Order/index.twig` `form_widget` calls. EC-CUBE renders the
 * search inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule — the same library the {@see AdminProductSearchForm}
 * (Product wave) and {@see AdminCustomerSearchForm} (Customer wave)
 * adopted.
 *
 * Scope — the resource-backed field only
 * --------------------------------------
 * EC-CUBE's `SearchOrderType` declares the multi-keyword box plus a long
 * detail panel (orderer name / kana / company, payment-method checkboxes,
 * order-status checkboxes, four date ranges, email / phone / order-no /
 * tracking-number / purchase-product / purchase-price / shipping-mail).
 * BeMart's {@see \MyVendor\BeMart\Resource\Page\Admin\OrderList} resource
 * (Wave 7 first iteration) supports exactly pagination knobs (`limit` /
 * `offset`) — no search axes (see `AdminOrderListFetched`: the admin
 * search form's orderNo / customerName / dateRange / orderStatus /
 * paymentMethod / deliveryMethod filters are Phase 2 scope).
 *
 * This form therefore declares ONLY `multi` (EC-CUBE's
 * `admin_search_order_multi` free-text keyword box). The detail-search
 * panel's other `form_widget` calls in the ported template render
 * empty — and EC-CUBE's real template, fed this same form, renders them
 * empty too, so the two diff to ZERO. When OrderList grows the extra
 * filter axes, the new fields are added here and the detail panel lights
 * up with no template change.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only (see var/templates/README.md).
 */
final class AdminOrderSearchForm extends AbstractForm
{
    /**
     * Declares the multi-keyword search box.
     *
     * `multi` is EC-CUBE's free-text keyword input (block prefix
     * `admin_search_order`, so the FormView id is
     * `admin_search_order_multi`). It matches on the order-no / orderer
     * name / email. The `sortkey` / `sorttype` hidden inputs EC-CUBE
     * emits below the panel are list-sort runtime state with no BeMart
     * equivalent; they are NOT declared (rendered empty on both sides).
     */
    #[Override]
    public function init(): void
    {
        $this->setField('multi', 'text')
            ->setAttribs([
                'id' => 'admin_search_order_multi',
                'class' => 'form-control',
            ]);
    }
}
