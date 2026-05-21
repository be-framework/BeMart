<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE ページ登録/編集フォーム（管理画面）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/MainEditType` + the
 * `admin/Content/page_edit.twig` `form_widget` calls. EC-CUBE renders
 * these inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html) — the same
 * recipe as the admin pilot {@see AdminNewsForm}.
 *
 * Field names ported from `MainEditType::buildForm()`: `name`, `url`,
 * `file_name`, `tpl_data` (textarea), plus the meta fields `author`,
 * `description`, `keyword`, `meta_robots`, `meta_tags`. EC-CUBE's block
 * prefix is `main_edit`, so the rendered ids are `main_edit_<field>`.
 *
 * The PC/Mobile layout selects (`PcLayout` / `SpLayout`) are NOT declared
 * here — the AdminPageFetched projection carries no page->layout join
 * (out of the Wave 9 CMS slice). The page_edit port renders those select
 * cells empty; see the template port header.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be Becoming chain (the
 * CreatePageInput / UpdatePageInput Semantics). This form is a
 * field-definition + renderer only. The `#[FormValidation]` aspect is
 * NOT used. See var/templates/README.md, "Admin pages".
 */
final class AdminPageForm extends AbstractForm
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
            ->setAttribs(['id' => 'main_edit_name', 'class' => 'form-control']);

        $this->setField('url', 'text')
            ->setAttribs(['id' => 'main_edit_url', 'class' => 'form-control']);

        $this->setField('file_name', 'text')
            ->setAttribs(['id' => 'main_edit_file_name', 'class' => 'form-control']);

        $this->setField('tpl_data', 'textarea')
            ->setAttribs(['id' => 'main_edit_tpl_data', 'class' => 'form-control']);

        // メタ設定 — author / description / keyword / robot / metatag.
        $this->setField('author', 'text')
            ->setAttribs(['id' => 'main_edit_author', 'class' => 'form-control']);
        $this->setField('description', 'text')
            ->setAttribs(['id' => 'main_edit_description', 'class' => 'form-control']);
        $this->setField('keyword', 'text')
            ->setAttribs(['id' => 'main_edit_keyword', 'class' => 'form-control']);
        $this->setField('meta_robots', 'text')
            ->setAttribs(['id' => 'main_edit_meta_robots', 'class' => 'form-control']);
        $this->setField('meta_tags', 'textarea')
            ->setAttribs(['id' => 'main_edit_meta_tags', 'class' => 'form-control']);

        // NON-AUTHORITATIVE structural checks only.
        $this->filter->validate('name')->isNotBlank();
        $this->filter->validate('url')->isNotBlank();
        $this->filter->validate('file_name')->isNotBlank();
    }

    /**
     * Repopulates the form inputs from the Page resource body.
     *
     * @param array<string, mixed> $body the Page resource body
     */
    public function fillValues(array $body): void
    {
        $this->fill([
            'name' => (string) ($body['pageName'] ?? ''),
            'url' => (string) ($body['pageUrl'] ?? ''),
            'file_name' => (string) ($body['pageFileName'] ?? ''),
            // The free-page template body + meta fields are out of the
            // Wave 9 CMS slice — see port header. Render empty.
            'tpl_data' => (string) ($body['pageTplData'] ?? ''),
            'author' => (string) ($body['pageAuthor'] ?? ''),
            'description' => (string) ($body['pageDescription'] ?? ''),
            'keyword' => (string) ($body['pageKeyword'] ?? ''),
            'meta_robots' => (string) ($body['pageMetaRobots'] ?? ''),
            'meta_tags' => (string) ($body['pageMetaTags'] ?? ''),
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
