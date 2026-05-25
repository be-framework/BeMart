<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 会員検索フォーム（管理画面 会員一覧）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/SearchCustomerType` + the
 * `admin/Customer/index.twig` `form_widget` calls. EC-CUBE renders the
 * search inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule, the same library the storefront {@see LoginForm} and
 * the admin {@see AdminNewsForm} pilots adopted.
 *
 * Scope — the resource-backed field only
 * --------------------------------------
 * EC-CUBE's `SearchCustomerType` declares ~25 filter fields (status, sex,
 * birth-month, prefecture, the seven date ranges, the purchase
 * aggregates, ...). BeMart's {@see \MyVendor\BeMart\Resource\Page\Admin\CustomerList}
 * resource (Wave 5 first iteration) supports exactly ONE search axis: the
 * free-text keyword (`nameKeyword` / `emailKeyword`, both fed from the
 * `multi` box). The remaining filter axes are out of the Wave 5 slice —
 * see CustomerList's docblock ("Phase 2 will add phoneNumber, dateRange,
 * purchaseAmount filters").
 *
 * This form therefore declares ONLY `multi` (the EC-CUBE
 * `admin_search_customer_multi` text box). The detail-search panel's
 * `form_widget` calls in the ported template render empty — and EC-CUBE's
 * real template, fed this same form, renders them empty too (the form
 * does not declare them), so the two diff to ZERO. The labels around the
 * empty widgets are static `trans` literals and match. When CustomerList
 * grows the extra filter axes, the new fields are added here and the
 * detail panel lights up with no template change.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only (see var/templates/README.md).
 */
final class AdminCustomerSearchForm extends AbstractForm
{
    /**
     * Declares the search box.
     *
     * `multi` is EC-CUBE's free-text keyword input (block prefix
     * `admin_search_customer`, so the FormView id is
     * `admin_search_customer_multi`). The `sortkey` / `sorttype` hidden
     * inputs EC-CUBE emits below the panel are list-sort runtime state
     * with no BeMart equivalent; they are NOT declared (rendered empty on
     * both sides — see the class doc).
     */
    #[Override]
    public function init(): void
    {
        $this->setField('multi', 'text')
            ->setAttribs([
                'id' => 'admin_search_customer_multi',
                'class' => 'form-control',
            ]);
    }

    /**
     * Repopulates the search box from the resource body filters.
     *
     * The CustomerList body exposes `filters.nameKeyword` /
     * `filters.emailKeyword`; the admin UI types ONE keyword into the
     * `multi` box, so whichever filter is set re-shows there.
     *
     * @param array<string, mixed> $filters the CustomerList `filters` body
     */
    public function fillFilters(array $filters): void
    {
        $keyword = $filters['nameKeyword'] ?? $filters['emailKeyword'] ?? '';
        $this->fill(['multi' => (string) $keyword]);
    }
}
