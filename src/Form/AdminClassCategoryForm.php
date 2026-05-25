<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 規格分類登録フォーム（管理画面 規格分類管理）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/ClassCategoryType` + the
 * `admin/Product/class_category.twig` `form_widget` calls. EC-CUBE
 * renders the inline create / edit inputs through the Symfony FormView;
 * BeMart renders the create inputs through Ray.WebFormModule.
 *
 * EC-CUBE's `ClassCategoryType` declares `name` (分類名) and
 * `backend_name` (管理名). BeMart's
 * {@see \MyVendor\BeMart\Resource\Page\Admin\ClassCategory\ClassCategoryList}
 * resource (Wave 7) projects only `classCategoryId` / `classNameId` /
 * `name` ({@see \MyVendor\BeMart\Be\Final\AdminClassCategoryListFetched}).
 * Both fields are declared here so the rendered inline-create form
 * carries EC-CUBE's two-input shape; `backend_name` repopulates empty
 * (the projection does not carry it — FLAGGED for enrichment follow-up).
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only (see var/templates/README.md).
 */
final class AdminClassCategoryForm extends AbstractForm
{
    /**
     * Declares the class-category inputs.
     *
     * EC-CUBE's `ClassCategoryType` block prefix is
     * `admin_class_category`, so the FormView ids are
     * `admin_class_category_<field>`.
     */
    #[Override]
    public function init(): void
    {
        $this->setField('name', 'text')
            ->setAttribs([
                'id' => 'admin_class_category_name',
                'class' => 'form-control',
            ]);

        $this->setField('backend_name', 'text')
            ->setAttribs([
                'id' => 'admin_class_category_backend_name',
                'class' => 'form-control',
            ]);

        // NON-AUTHORITATIVE structural check only — the authoritative
        // class-category-name rule lives in the Be domain
        // (CreateClassCategoryInput Semantic).
        $this->filter->validate('name')->isNotBlank();
    }
}
