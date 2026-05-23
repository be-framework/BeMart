<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE テンプレートアップロードフォーム — Admin Store Tier-2.
 *
 * Thin renderer form for `admin/Store/template_add.twig`. The real
 * template archive validation / install pipeline is outside this HTML
 * port; validation authority stays with a future Be transition.
 */
final class AdminTemplateAddForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('code', 'text')
            ->setAttribs(['id' => 'form_code', 'class' => 'form-control']);

        $this->setField('name', 'text')
            ->setAttribs(['id' => 'form_name', 'class' => 'form-control']);

        $this->setField('file', 'file')
            ->setAttribs([
                'id' => 'form_file',
                'class' => 'form-control',
                'accept' => 'application/zip,application/x-tar,application/x-gzip',
            ]);
    }
}
