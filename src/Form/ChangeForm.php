<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE doUpdateCustomer のマイページ会員情報編集フォーム —
 * Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Front/EntryType` (reused by the
 * mypage_change controller) + the `Mypage/change.twig` `form_widget`
 * calls. EC-CUBE renders these inputs through the Symfony FormView;
 * BeMart renders them through Ray.WebFormModule (Aura.Input +
 * Aura.Filter + Aura.Html).
 *
 * Role of this class (the form-page recipe — see var/templates/README.md):
 *
 *  - **Field definition** — `init()` declares the profile-edit inputs
 *    with the EC-CUBE field names / placeholders so the rendered
 *    `<input>` / `<select>` markup reproduces EC-CUBE's `ec-*` form.
 *    EC-CUBE's `EntryType` nests fields under compound types
 *    (`form.name.name01`, `form.email.first`), but BeMart's Change
 *    resource body carries the fields FLAT (`name01`, `email`, ...), so
 *    the form declares them flat to match the resource.
 *  - **HTML rendering** — `{{ form.input('name01') }}` in
 *    `Change.html.twig`.
 *  - **Repopulation** — `onGet` pre-populates the form with the
 *    logged-in customer's current profile via {@see fillValues()}.
 *
 * What this class deliberately does NOT do — **validation authority**:
 *
 *   BeMart validates the profile update in the domain via Be Framework
 *   Semantics (UpdateCustomerInput) and the Final/exception layer
 *   (EmailAlreadyRegisteredException). The `#[FormValidation]` aspect is
 *   NOT used — validation authority stays with the Be Becoming chain.
 *
 * @link https://schema.org/UpdateAction
 */
final class ChangeForm extends AbstractForm
{
    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the profile-edit form fields.
     *
     * Field names / placeholders are ported from EC-CUBE's `EntryType`
     * leaf fields + `Mypage/change.twig`'s `form_widget` `attr` options.
     * `pref` / `birth_*` / `sex` / `job` are `<select>` / `<radio>`
     * widgets whose option sets are EC-CUBE master data the BeMart
     * resource body does not carry — they render as the bare empty
     * control, the option sets enumerated as an EC-CUBE-runtime residual.
     */
    #[Override]
    public function init(): void
    {
        // お名前 / お名前(カナ).
        $this->setField('name01', 'text')->setAttribs(['placeholder' => '姓']);
        $this->setField('name02', 'text')->setAttribs(['placeholder' => '名']);
        $this->setField('kana01', 'text')->setAttribs(['placeholder' => 'セイ']);
        $this->setField('kana02', 'text')->setAttribs(['placeholder' => 'メイ']);

        // 会社名 (optional).
        $this->setField('companyName', 'text');

        // 住所 — postal code + prefecture select + address lines.
        $this->setField('postalCode', 'text');
        $this->setField('pref', 'select')->setOptions([]);
        $this->setField('addr01', 'text')
            ->setAttribs(['placeholder' => '市区町村名(例：千代田区)']);
        $this->setField('addr02', 'text')
            ->setAttribs(['placeholder' => '番地・ビル名(例：神田1-1-1)']);

        // 電話番号.
        $this->setField('phoneNumber', 'text');

        // メールアドレス + 確認.
        $this->setField('email', 'text')
            ->setAttribs(['placeholder' => '例：ec-cube@example.com']);
        $this->setField('email_confirm', 'text')
            ->setAttribs(['placeholder' => '確認のためもう一度入力してください']);

        // パスワード + 確認.
        $this->setField('password', 'password');
        $this->setField('password_confirm', 'password')
            ->setAttribs(['placeholder' => '確認のためもう一度入力してください']);

        // 生年月日 — three selects.
        $this->setField('birth_year', 'select')->setOptions([]);
        $this->setField('birth_month', 'select')->setOptions([]);
        $this->setField('birth_day', 'select')->setOptions([]);

        // 性別 — radio.
        $this->setField('sex', 'radio')->setOptions([]);

        // 職業 — select.
        $this->setField('job', 'select')->setOptions([]);

        // NON-AUTHORITATIVE structural checks only — authority is the Be
        // domain (UpdateCustomerInput Semantics).
        $this->filter->validate('name01')->isNotBlank();
        $this->filter->validate('name02')->isNotBlank();
        $this->filter->validate('email')->isNotBlank();
    }

    /**
     * Repopulates the form inputs with values.
     *
     * `onGet` calls this to pre-populate the edit form with the current
     * profile; a failed POST repopulates the submitted values. Password
     * fields are intentionally never repopulated.
     *
     * @param array<string, scalar|null> $values field name => value
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }

    /**
     * Bridges a Be-domain rejection onto the form's error state.
     */
    public function setDomainError(string $field, string $message): void
    {
        $this->domainErrors[$field] = $message;
    }

    /**
     * Returns the error message for a field — Be-domain errors take
     * precedence over the Aura.Filter structural message.
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
