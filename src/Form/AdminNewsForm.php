<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 新着情報登録/編集フォーム — admin pilot, form-page recipe.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/NewsType` + the
 * `admin/Content/news_edit.twig` `form_widget` calls. EC-CUBE renders
 * these inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html) — the same
 * library the storefront's {@see LoginForm} pilot adopted.
 *
 * This is the admin pilot's FORM/CRUD page. The recipe is identical to
 * the storefront form-page recipe (see var/templates/README.md): the
 * form is a FIELD-DEFINITION + RENDERER only — VALIDATION AUTHORITY
 * STAYS WITH the Be Framework Becoming chain. The News resource hands
 * the raw input to Becoming; on a `SemanticVariableException` it bridges
 * the verdict onto this form (repopulated values + inline error) so the
 * page re-renders with EC-CUBE's exact form UX. The `#[FormValidation]`
 * aspect is NOT used.
 *
 * EC-CUBE's `NewsType` declares `title`, `publish_date`, `url`,
 * `link_method`, and `description`. BeMart's unsafe Resource boundary
 * accepts the canonical `newsTitle` / `publishDate` / etc. parameters,
 * so the rendered fields keep EC-CUBE's `admin_news_<field>` ids while
 * posting those canonical names.
 */
final class AdminNewsForm extends AbstractForm
{
    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name. Consulted by {@see error()} so a field error renders
     * the Be-domain message, not an Aura.Filter message.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the news form fields.
     *
     * Ported from EC-CUBE's `NewsType::buildForm()` + `news_edit.twig`.
     * The field ids remain EC-CUBE compatible; the posted names are
     * BeMart's canonical Resource request names.
     */
    #[Override]
    public function init(): void
    {
        $this->setField('publishDate', 'text')
            ->setAttribs([
                'id' => 'admin_news_publish_date',
                'class' => 'form-control',
            ]);

        $this->setField('newsTitle', 'text')
            ->setAttribs([
                'id' => 'admin_news_title',
                'class' => 'form-control',
            ]);

        $this->setField('newsUrl', 'text')
            ->setAttribs([
                'id' => 'admin_news_url',
                'class' => 'form-control',
            ]);

        // EC-CUBE renders link_method as a single checkbox whose checked
        // state maps to dtb_news.link_method (target="_blank"). Aura.Html's
        // checkbox helper treats `options` as value=>label pairs; passing
        // a single pair yields one labelled checkbox with `value="1"`.
        $this->setField('linkMethod', 'checkbox')
            ->setAttribs([
                'id' => 'admin_news_link_method',
            ])
            ->setOptions(['1' => '別ウィンドウで開く']);

        $this->setField('newsDescription', 'textarea')
            ->setAttribs([
                'id' => 'admin_news_description',
                'class' => 'form-control',
                'rows' => '8',
            ]);

        // NON-AUTHORITATIVE structural checks only. The authoritative
        // title / url / publish-date rules live in the Be domain
        // (CreateNewsInput / UpdateNewsInput Semantics). The News
        // resource does not consult this filter.
        $this->filter->validate('publishDate')->isNotBlank();
        $this->filter->validate('newsTitle')->isNotBlank();
    }

    /**
     * Repopulates the form inputs from the News resource body.
     *
     * Called by the News resource so the edit page pre-fills with the
     * persisted row, and re-fills with the submitted values after a
     * rejected POST.
     *
     * @param array<string, mixed> $body the News resource body
     */
    public function fillValues(array $body): void
    {
        $values = [
            'publishDate' => (string) ($body['publishDate'] ?? ''),
            'newsTitle' => (string) ($body['newsTitle'] ?? ''),
            'newsUrl' => (string) ($body['newsUrl'] ?? ''),
            'newsDescription' => (string) ($body['newsDescription'] ?? ''),
        ];
        if (! empty($body['linkMethod'])) {
            $values['linkMethod'] = '1';
        }

        $this->fill($values);
    }

    /**
     * Bridges a Be-domain rejection onto the form's error state.
     *
     * The News resource calls this when the Becoming chain rejects the
     * input (a SemanticVariableException). The message then surfaces
     * through `{{ form.error(field) }}`. Validation authority stays with
     * Be — this only transports a verdict the domain already reached.
     */
    public function setDomainError(string $field, string $message): void
    {
        $this->domainErrors[$field] = $message;
    }

    /**
     * Returns the error message for a field.
     *
     * Be-domain errors (bridged via {@see setDomainError()}) take
     * precedence — they are the authoritative verdict. Falls back to the
     * Aura.Filter structural message only if no domain error is present.
     */
    #[Override]
    public function error(string $input): string
    {
        if (isset($this->domainErrors[$input])) {
            return $this->domainErrors[$input];
        }

        return parent::error($input);
    }
}
