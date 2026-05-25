<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE カスタマイズJavaScript編集フォーム（管理画面）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `JsController`'s inline `createFormBuilder` +
 * the `admin/Content/js.twig` `form_widget(form.js)` call. EC-CUBE builds
 * the form inline (default block prefix `form`), so the rendered id is
 * `form_js`. BeMart renders the textarea through Ray.WebFormModule — the
 * same recipe as the admin pilot {@see AdminNewsForm}.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be Becoming chain. This form is a
 * field-definition + renderer only. The `#[FormValidation]` aspect is
 * NOT used. See var/templates/README.md, "Admin pages".
 */
final class AdminJsForm extends AbstractForm
{
    /** @var array<string, string> */
    private array $domainErrors = [];

    #[Override]
    public function init(): void
    {
        $this->setField('js', 'textarea')
            ->setAttribs([
                'id' => 'form_js',
                'class' => 'form-control',
            ]);
    }

    /**
     * Repopulates the form input from the Js resource body.
     *
     * @param array<string, mixed> $body the Js resource body
     */
    public function fillValues(array $body): void
    {
        $this->fill([
            'js' => (string) ($body['js'] ?? ''),
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
