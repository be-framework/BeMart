<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE レイアウト編集フォーム（管理画面）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/LayoutType` + the
 * `admin/Content/layout.twig` `form_widget` calls. EC-CUBE renders these
 * inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule — the same recipe as the admin pilot {@see AdminNewsForm}.
 *
 * Field names ported from `LayoutType::buildForm()`: `name` (text). The
 * `DeviceType` select and the `Page` preview select are NOT declared
 * here — for an existing layout EC-CUBE renders the device type as a
 * static label + hidden input (the layout.twig `{% if Layout.id %}`
 * branch), and the `Page` preview select is gated on a non-empty page
 * collection the AdminLayoutFetched projection does not carry. The
 * layout.twig port renders those branches as residual; see the port
 * header. EC-CUBE's block prefix is `admin_layout`, so the rendered id
 * is `admin_layout_name`.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be Becoming chain (the
 * UpdateLayoutInput Semantics). This form is a field-definition +
 * renderer only. The `#[FormValidation]` aspect is NOT used.
 */
final class AdminLayoutForm extends AbstractForm
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
                'id' => 'admin_layout_name',
                'class' => 'form-control',
            ]);

        // NON-AUTHORITATIVE structural check only.
        $this->filter->validate('name')->isNotBlank();
    }

    /**
     * Repopulates the form inputs from the Layout resource body.
     *
     * @param array<string, mixed> $body the Layout resource body
     */
    public function fillValues(array $body): void
    {
        $this->fill([
            'name' => (string) ($body['layoutName'] ?? ''),
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
