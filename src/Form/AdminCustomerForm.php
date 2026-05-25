<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE 会員登録/編集フォーム（管理画面）— Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/CustomerType` + the
 * `admin/Customer/edit.twig` `form_widget` calls. EC-CUBE renders these
 * inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html) — the same
 * recipe as the admin pilot {@see AdminNewsForm}.
 *
 * Flat field names — EC-CUBE's `CustomerType` nests fields under compound
 * types (`form.name.name01`, `form.address.pref`, `form.plain_password.first`),
 * but BeMart's {@see \MyVendor\BeMart\Resource\Page\Admin\Customer} body
 * carries the profile FLAT (`name01`, `kana01`, `pref`, ...) — same
 * decision as the storefront {@see EntryForm}. The leaf names are the
 * EC-CUBE leaf names; the rendered `<input>` ids reproduce the FormView
 * ids EC-CUBE's `admin_customer` block prefix produces.
 *
 * VALIDATION AUTHORITY STAYS WITH the Be Becoming chain (the
 * AdminCreateCustomerInput / GetAdminCustomerInput Semantics + the
 * Final/exception layer). This form is a field-definition + renderer
 * only; the resource never consults the Aura.Filter verdict. The
 * `#[FormValidation]` aspect is NOT used. See var/templates/README.md.
 */
final class AdminCustomerForm extends AbstractForm
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
     * Declares the customer edit/create form fields.
     *
     * Ported from EC-CUBE's `CustomerType` leaf fields + `edit.twig`'s
     * `form_widget` calls. EC-CUBE's block prefix is `admin_customer`, so
     * the FormView ids are `admin_customer_<nested>_<leaf>`; the flat
     * field declared here carries that id explicitly.
     */
    #[Override]
    public function init(): void
    {
        // お名前 / お名前(カナ) — name + kana pairs (NameType / KanaType).
        $this->setField('name01', 'text')
            ->setAttribs(['id' => 'admin_customer_name_name01', 'class' => 'form-control']);
        $this->setField('name02', 'text')
            ->setAttribs(['id' => 'admin_customer_name_name02', 'class' => 'form-control']);
        $this->setField('kana01', 'text')
            ->setAttribs(['id' => 'admin_customer_kana_kana01', 'class' => 'form-control']);
        $this->setField('kana02', 'text')
            ->setAttribs(['id' => 'admin_customer_kana_kana02', 'class' => 'form-control']);

        // 会社名 (optional).
        $this->setField('company_name', 'text')
            ->setAttribs(['id' => 'admin_customer_company_name', 'class' => 'form-control']);

        // 住所 — postal code + prefecture select + address lines.
        $this->setField('postal_code', 'text')
            ->setAttribs(['id' => 'admin_customer_postal_code', 'class' => 'form-control']);
        // pref is an EC-CUBE master-data <select> (mtb_pref); the option
        // set is Doctrine data the resource body does not carry, so the
        // control renders with no <option>s — enumerated as residual.
        $this->setField('pref', 'select')
            ->setAttribs(['id' => 'admin_customer_address_pref', 'class' => 'form-select'])
            ->setOptions([]);
        $this->setField('addr01', 'text')
            ->setAttribs(['id' => 'admin_customer_address_addr01', 'class' => 'form-control']);
        $this->setField('addr02', 'text')
            ->setAttribs(['id' => 'admin_customer_address_addr02', 'class' => 'form-control']);

        // メールアドレス.
        $this->setField('email', 'text')
            ->setAttribs([
                'id' => 'admin_customer_email', 'class' => 'form-control',
                'placeholder' => '例：ec-cube@example.com',
            ]);

        // 電話番号.
        $this->setField('phone_number', 'text')
            ->setAttribs(['id' => 'admin_customer_phone_number', 'class' => 'form-control']);

        // パスワード + 確認 (RepeatedPasswordType -> first / second).
        $this->setField('plain_password_first', 'password')
            ->setAttribs(['id' => 'admin_customer_plain_password_first', 'class' => 'form-control']);
        $this->setField('plain_password_second', 'password')
            ->setAttribs(['id' => 'admin_customer_plain_password_second', 'class' => 'form-control']);

        // 性別 — radio (SexType, mtb_sex master data — empty option set).
        $this->setField('sex', 'radio')
            ->setOptions([]);

        // 職業 — select (JobType, mtb_job master data — empty option set).
        $this->setField('job', 'select')
            ->setAttribs(['id' => 'admin_customer_job', 'class' => 'form-select'])
            ->setOptions([]);

        // 誕生日 — single-text date.
        $this->setField('birth', 'text')
            ->setAttribs(['id' => 'admin_customer_birth', 'class' => 'form-control']);

        // ポイント — number.
        $this->setField('point', 'text')
            ->setAttribs(['id' => 'admin_customer_point', 'class' => 'form-control']);

        // ショップ用メモ欄 — textarea.
        $this->setField('note', 'textarea')
            ->setAttribs(['id' => 'admin_customer_note', 'class' => 'form-control', 'rows' => '8']);

        // NON-AUTHORITATIVE structural checks only. The authoritative
        // rules live in the Be domain (AdminCreateCustomerInput Semantics).
        $this->filter->validate('name01')->isNotBlank();
        $this->filter->validate('name02')->isNotBlank();
        $this->filter->validate('email')->isNotBlank();
    }

    /**
     * Repopulates the form from a Customer resource body.
     *
     * Maps the flat body keys onto the form field names. The password
     * fields are intentionally NOT repopulated (never echo a credential).
     *
     * @param array<string, mixed> $body the Customer resource body
     */
    public function fillValues(array $body): void
    {
        $this->fill([
            'name01' => (string) ($body['name01'] ?? ''),
            'name02' => (string) ($body['name02'] ?? ''),
            'kana01' => (string) ($body['kana01'] ?? ''),
            'kana02' => (string) ($body['kana02'] ?? ''),
            'company_name' => (string) ($body['companyName'] ?? ''),
            'postal_code' => (string) ($body['postalCode'] ?? ''),
            'addr01' => (string) ($body['addr01'] ?? ''),
            'addr02' => (string) ($body['addr02'] ?? ''),
            'email' => (string) ($body['email'] ?? ''),
            'phone_number' => (string) ($body['phoneNumber'] ?? ''),
            'birth' => (string) ($body['birth'] ?? ''),
            'point' => (string) ($body['initialPoint'] ?? ''),
        ]);
    }

    /**
     * Bridges a Be-domain rejection onto the form's error state. The
     * Becoming chain reached the verdict; the form only transports it.
     */
    public function setDomainError(string $field, string $message): void
    {
        $this->domainErrors[$field] = $message;
    }

    /**
     * Returns the error for a field — bridged Be-domain errors take
     * precedence over the non-authoritative Aura.Filter message.
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
