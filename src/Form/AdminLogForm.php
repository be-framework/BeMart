<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE ログ表示フォーム — Setting/System Tier-2.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/LogType` + the
 * `admin/Setting/System/log.twig` `form_widget` calls. The form is a
 * renderer only: log-file reads are handled by the `Admin\Log` resource
 * and no Be validation verdict is delegated to Aura.Filter.
 */
final class AdminLogForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('files', 'select')
            ->setAttribs([
                'id' => 'admin_system_log_files',
                'class' => 'form-select',
            ])
            ->setOptions(['site.log' => 'site.log', 'bemart.json' => 'bemart.json']);

        $this->setField('line_max', 'text')
            ->setAttribs([
                'id' => 'admin_system_log_line_max',
                'class' => 'form-control',
                'maxlength' => '5',
            ]);

        $this->filter->validate('line_max')->isNotBlank();
    }

    public function fillValues(string $selectedFile, int $lineMax): void
    {
        $this->fill([
            'files' => $selectedFile,
            'line_max' => (string) $lineMax,
        ]);
    }
}
