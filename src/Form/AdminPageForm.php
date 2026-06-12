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
 * EC-CUBE's `MainEditType` declares `name`, `url`, and `file_name`.
 * BeMart's unsafe Resource boundary accepts the canonical `pageName`,
 * `pageUrl`, and `pageFileName` parameters, so the rendered fields keep
 * EC-CUBE's `main_edit_<field>` ids while posting those canonical names.
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
        $this->setField('pageName', 'text')
            ->setAttribs(['id' => 'main_edit_name', 'class' => 'form-control']);

        $this->setField('pageUrl', 'text')
            ->setAttribs(['id' => 'main_edit_url', 'class' => 'form-control']);

        $this->setField('pageFileName', 'text')
            ->setAttribs(['id' => 'main_edit_file_name', 'class' => 'form-control']);

        $this->setField('tpl_data', 'textarea')
            ->setAttribs(['id' => 'main_edit_tpl_data', 'class' => 'form-control', 'disabled' => 'disabled']);

        // メタ設定 — author / description / keyword / robot / metatag.
        $this->setField('author', 'text')
            ->setAttribs(['id' => 'main_edit_author', 'class' => 'form-control', 'disabled' => 'disabled']);
        $this->setField('description', 'text')
            ->setAttribs(['id' => 'main_edit_description', 'class' => 'form-control', 'disabled' => 'disabled']);
        $this->setField('keyword', 'text')
            ->setAttribs(['id' => 'main_edit_keyword', 'class' => 'form-control', 'disabled' => 'disabled']);
        $this->setField('meta_robots', 'text')
            ->setAttribs(['id' => 'main_edit_meta_robots', 'class' => 'form-control', 'disabled' => 'disabled']);
        $this->setField('meta_tags', 'textarea')
            ->setAttribs(['id' => 'main_edit_meta_tags', 'class' => 'form-control', 'disabled' => 'disabled']);

        // NON-AUTHORITATIVE structural checks only.
        $this->filter->validate('pageName')->isNotBlank();
        $this->filter->validate('pageUrl')->isNotBlank();
        $this->filter->validate('pageFileName')->isNotBlank();
    }

    /**
     * Repopulates the form inputs from the Page resource body.
     *
     * @param array<string, mixed> $body the Page resource body
     */
    public function fillValues(array $body): void
    {
        $this->fill([
            'pageName' => (string) ($body['pageName'] ?? ''),
            'pageUrl' => (string) ($body['pageUrl'] ?? ''),
            'pageFileName' => (string) ($body['pageFileName'] ?? ''),
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
