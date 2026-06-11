<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE テンプレート登録フォーム — Store Tier-2.
 *
 * PORT of the form rendered by `admin/Store/template_add.twig` — the
 * テンプレート登録 (shop design-template upload) screen. EC-CUBE renders
 * it through `Form/Type/Admin/TemplateType` (`admin_template` block
 * prefix): a template code, a template name and a zip-archive file
 * `<input type="file">`.
 *
 * The `<input type="file">` ports as a plain static input — exactly as
 * the Product CSV-upload wave's {@see AdminCsvUploadForm}.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be domain — this form is a
 * field-definition + renderer only (see var/templates/README.md).
 */
final class AdminTemplateAddForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('templateCode', 'text')
            ->setAttribs(['id' => 'admin_template_code', 'class' => 'form-control', 'maxlength' => '255']);

        $this->setField('templateName', 'text')
            ->setAttribs(['id' => 'admin_template_name', 'class' => 'form-control', 'maxlength' => '255']);

        $this->setField('file', 'file')
            ->setAttribs(['id' => 'admin_template_file', 'class' => 'form-control']);

        // Non-authoritative structural checks only — authority is the Be domain.
        $this->filter->validate('templateCode')->isNotBlank();
        $this->filter->validate('templateName')->isNotBlank();
        $this->filter->validate('file')->isNotBlank();
    }
}
