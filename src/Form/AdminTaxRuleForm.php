<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 税率設定フォーム（管理画面 税率設定）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/TaxRuleType` + the
 * `admin/Setting/Shop/tax_rule.twig` `form_widget` calls. EC-CUBE
 * renders the inline create / edit inputs through the Symfony FormView;
 * BeMart renders the create inputs through Ray.WebFormModule, the same
 * library the {@see AdminTagForm} / {@see AdminClassNameForm} ports
 * adopted.
 *
 * Scope — the inline-create form only
 * -----------------------------------
 * EC-CUBE's `tax_rule.twig` renders TWO form families: the top-of-table
 * inline-CREATE form (`form.tax_rate` / `form.rounding_type` /
 * `form.apply_date`) and a per-row inline-EDIT form
 * (`forms[TaxRule.id].*`). BeMart's
 * {@see \MyVendor\BeMart\Resource\Page\Admin\TaxRule\TaxRuleList}
 * resource exposes `doCreateTaxRule` (POST) and `doDeleteTaxRule`
 * (DELETE) — there is NO `doUpdateTaxRule` (the alps.json profile
 * routes edits as delete-then-create — see the TaxRule resource
 * docblock). This form therefore declares ONLY the inline-create
 * fields; the ported template renders the per-row values as plain text.
 *
 * Omitted control — `rounding_type` (課税規則)
 * --------------------------------------------
 * EC-CUBE's `rounding_type` is a `RoundingTypeType` select backed by
 * the `mtb_rounding_type` master-data table. BeMart's
 * AdminTaxRuleListFetched projection carries `roundingType` only as a
 * bare int with no joined option set, so the select is omitted; the
 * 課税規則 cell renders without an input. FLAGGED missing-body-field —
 * enumerated as a render-test residual.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only (see var/templates/README.md).
 */
final class AdminTaxRuleForm extends AbstractForm
{
    /**
     * Declares the tax-rule inline-create inputs.
     *
     * EC-CUBE's `TaxRuleType` block prefix is `tax_rule`, so the
     * FormView ids are `tax_rule_<field>`. `tax_rate` is an
     * `IntegerType` (numeric input); `apply_date` is a `DateTimeType`
     * with `widget: single_text` (a `datetime-local` input).
     */
    #[Override]
    public function init(): void
    {
        $this->setField('tax_rate', 'number')
            ->setAttribs([
                'id' => 'tax_rule_tax_rate',
                'class' => 'form-control',
            ]);

        $this->setField('apply_date', 'datetime-local')
            ->setAttribs([
                'id' => 'tax_rule_apply_date',
                'class' => 'form-control',
            ]);

        // NON-AUTHORITATIVE structural check only — the authoritative
        // tax-rate / apply-date rules live in the Be domain
        // (CreateTaxRuleInput Semantic).
        $this->filter->validate('tax_rate')->isNotBlank();
        $this->filter->validate('apply_date')->isNotBlank();
    }
}
