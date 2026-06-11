<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 規格登録フォーム（管理画面 規格管理）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/ClassNameType` + the
 * `admin/Product/class_name.twig` `form_widget` calls. EC-CUBE renders
 * the inline create / edit inputs through the Symfony FormView; BeMart
 * renders the create inputs through Ray.WebFormModule.
 *
 * EC-CUBE's `ClassNameType` declares `name` (規格名) and `backend_name`
 * (管理名). BeMart's unsafe Resource boundary accepts the canonical
 * `classNameLabel` parameter, so the rendered field keeps EC-CUBE's
 * `admin_class_name_name` id while posting that canonical name.
 * BeMart's
 * {@see \MyVendor\BeMart\Resource\Page\Admin\ClassName\ClassNameList}
 * resource (Wave 7) projects only `classNameId` / `name`
 * ({@see \MyVendor\BeMart\Be\Final\AdminClassNameListFetched}). Both
 * `backend_name` remains a displayed residual only (the projection does
 * not carry it — FLAGGED for enrichment follow-up) and must not be
 * submitted to the canonical Resource request schema.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only (see var/templates/README.md).
 */
final class AdminClassNameForm extends AbstractForm
{
    /**
     * Declares the class-name inputs.
     *
     * EC-CUBE's `ClassNameType` block prefix is `admin_class_name`, so
     * the FormView ids are `admin_class_name_<field>`.
     */
    #[Override]
    public function init(): void
    {
        $this->setField('classNameLabel', 'text')
            ->setAttribs([
                'id' => 'admin_class_name_name',
                'class' => 'form-control',
            ]);

        $this->setField('backend_name', 'text')
            ->setAttribs([
                'id' => 'admin_class_name_backend_name',
                'class' => 'form-control',
            ]);

        // NON-AUTHORITATIVE structural check only — the authoritative
        // class-name rule lives in the Be domain (CreateClassNameInput
        // Semantic).
        $this->filter->validate('classNameLabel')->isNotBlank();
    }
}
