<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE メール設定フォーム — Setting/Shop Tier-2.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/MailType` leaf fields. File
 * writes and Twig linting remain outside this GET-renderer wave.
 */
final class AdminMailTemplateForm extends AbstractForm
{
    /** @var array<int|string, string> — numeric-string keys coerce to int; '' stays string */
    private const TEMPLATE_OPTIONS = [
        '' => '選択してください',
        '1' => '注文完了メール',
        '2' => '会員登録完了メール',
    ];

    #[Override]
    public function init(): void
    {
        $this->setField('template', 'select')
            ->setAttribs(['id' => 'mail_template', 'class' => 'form-select'])
            ->setOptions(self::TEMPLATE_OPTIONS);
        $this->setField('name', 'text')
            ->setAttribs(['id' => 'mail_name', 'class' => 'form-control']);
        $this->setField('file_name', 'text')
            ->setAttribs(['id' => 'mail_file_name', 'class' => 'form-control']);
        $this->setField('mail_subject', 'text')
            ->setAttribs(['id' => 'mail_mail_subject', 'class' => 'form-control']);
        $this->setField('tpl_data', 'textarea')
            ->setAttribs(['id' => 'mail_tpl_data', 'class' => 'form-control', 'rows' => '12']);
        $this->setField('html_tpl_data', 'textarea')
            ->setAttribs(['id' => 'mail_html_tpl_data', 'class' => 'form-control', 'rows' => '12']);

        $this->filter->validate('name')->isNotBlank();
        $this->filter->validate('mail_subject')->isNotBlank();
    }

    /**
     * @param array{
     *   template: string,
     *   name: string,
     *   file_name: string,
     *   mail_subject: string,
     *   tpl_data: string,
     *   html_tpl_data: string
     * } $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
