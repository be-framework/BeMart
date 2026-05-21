<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE カテゴリ登録・編集フォーム — Product Tier-2.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/CategoryType` used by
 * `admin/Product/category.twig` — the category tree-list + inline
 * add/edit screen. EC-CUBE's `CategoryType` declares `name` (カテゴリ名);
 * the parent is taken from the tree row the admin is editing under.
 * BeMart's {@see \MyVendor\BeMart\Be\Final\AdminCategoryFetched}
 * projection carries `categoryName` / `parentId` / `sortNo`, so this
 * form ports the editable `name` field plus a hidden-style `parent_id`
 * the tree row supplies.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only (see var/templates/README.md).
 */
final class AdminCategoryForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('name', 'text')
            ->setAttribs(['id' => 'admin_category_name', 'class' => 'form-control', 'placeholder' => 'カテゴリ名']);
        $this->setField('parent_id', 'text')
            ->setAttribs(['id' => 'admin_category_parent_id', 'class' => 'form-control']);
        $this->setField('sort_no', 'text')
            ->setAttribs(['id' => 'admin_category_sort_no', 'class' => 'form-control']);

        // Non-authoritative structural check only — authority is the Be domain.
        $this->filter->validate('name')->isNotBlank();
    }

    /**
     * Pre-populates the editor with a category-detail projection.
     *
     * @param array<string, scalar|null> $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
