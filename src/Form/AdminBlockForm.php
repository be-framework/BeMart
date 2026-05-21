<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE ブロック登録/編集フォーム（管理画面）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/BlockType` + the
 * `admin/Content/block_edit.twig` `form_widget` calls. EC-CUBE renders
 * these inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html) — the same
 * recipe as the admin pilot {@see AdminNewsForm}.
 *
 * Field names ported from `BlockType::buildForm()`: `name` (text),
 * `file_name` (text), `block_html` (textarea). EC-CUBE's block prefix is
 * `block`, so the rendered ids are `block_<field>`.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be Becoming chain (the
 * CreateBlockInput / UpdateBlockInput Semantics). This form is a
 * field-definition + renderer only; the resource never consults the
 * Aura.Filter verdict. The `#[FormValidation]` aspect is NOT used.
 * See var/templates/README.md, "Admin pages".
 */
final class AdminBlockForm extends AbstractForm
{
    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    #[Override]
    public function init(): void
    {
        $this->setField('name', 'text')
            ->setAttribs([
                'id' => 'block_name',
                'class' => 'form-control',
            ]);

        $this->setField('file_name', 'text')
            ->setAttribs([
                'id' => 'block_file_name',
                'class' => 'form-control',
            ]);

        $this->setField('block_html', 'textarea')
            ->setAttribs([
                'id' => 'block_block_html',
                'class' => 'form-control',
            ]);

        // NON-AUTHORITATIVE structural checks only. Authoritative rules
        // live in the Be domain.
        $this->filter->validate('name')->isNotBlank();
        $this->filter->validate('file_name')->isNotBlank();
    }

    /**
     * Repopulates the form inputs from the Block resource body.
     *
     * @param array<string, mixed> $body the Block resource body
     */
    public function fillValues(array $body): void
    {
        $this->fill([
            'name' => (string) ($body['blockName'] ?? ''),
            'file_name' => (string) ($body['blockFileName'] ?? ''),
            // The block source code (dtb_block tpl) is out of the Wave 9
            // CMS slice — see AdminBlockForm port header. Renders empty.
            'block_html' => (string) ($body['blockHtml'] ?? ''),
        ]);
    }

    public function setDomainError(string $field, string $message): void
    {
        $this->domainErrors[$field] = $message;
    }

    #[Override]
    public function error(string $input): string
    {
        if (isset($this->domainErrors[$input])) {
            return $this->domainErrors[$input];
        }

        return parent::error($input);
    }
}
