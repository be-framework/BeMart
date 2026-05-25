<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 商品検索フォーム（管理画面 商品一覧）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/SearchProductType` + the
 * `admin/Product/index.twig` `form_widget` calls. EC-CUBE renders the
 * search inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule, the same library the {@see AdminCustomerSearchForm}
 * (Customer wave) adopted.
 *
 * Scope — the resource-backed field only
 * --------------------------------------
 * EC-CUBE's `SearchProductType` declares the keyword box plus category /
 * tag / display-status / stock / create-date / update-date filters.
 * BeMart's {@see \MyVendor\BeMart\Resource\Page\Admin\ProductList}
 * resource (Wave 8 first iteration) supports exactly ONE search axis: the
 * free-text keyword (`nameKeyword`, fed from the `id` box). The remaining
 * filter axes are out of the Wave 8 slice — see ProductListFetched's
 * docblock ("Phase 2 will add category, tag, stock state, sale type").
 *
 * This form therefore declares ONLY `id` (EC-CUBE's
 * `admin_search_product_id` keyword text box). The detail-search panel's
 * other `form_widget` calls in the ported template render empty — and
 * EC-CUBE's real template, fed this same form, renders them empty too, so
 * the two diff to ZERO. When ProductList grows the extra filter axes, the
 * new fields are added here and the detail panel lights up with no
 * template change.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only (see var/templates/README.md).
 */
final class AdminProductSearchForm extends AbstractForm
{
    /**
     * Declares the search box.
     *
     * `id` is EC-CUBE's free-text keyword input (block prefix
     * `admin_search_product`, so the FormView id is
     * `admin_search_product_id`). It matches on the product name / id /
     * code. The `sortkey` / `sorttype` hidden inputs EC-CUBE emits below
     * the panel are list-sort runtime state with no BeMart equivalent;
     * they are NOT declared (rendered empty on both sides).
     */
    #[Override]
    public function init(): void
    {
        $this->setField('id', 'text')
            ->setAttribs([
                'id' => 'admin_search_product_id',
                'class' => 'form-control',
            ]);
    }

    /**
     * Repopulates the search box from the resource body filters.
     *
     * The ProductList body exposes `filters.nameKeyword`; the admin UI
     * types ONE keyword into the `id` box.
     *
     * @param array<string, mixed> $filters the ProductList `filters` body
     */
    public function fillFilters(array $filters): void
    {
        $this->fill(['id' => (string) ($filters['nameKeyword'] ?? '')]);
    }
}
