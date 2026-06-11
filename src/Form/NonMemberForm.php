<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE goShoppingNonMember の非会員購入者情報入力フォーム —
 * Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Front/NonMemberType` + the
 * `Shopping/nonmember.twig` `form_widget` calls. EC-CUBE renders these
 * inputs through the Symfony FormView; BeMart renders them through
 * Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html).
 *
 * Role of this class (the form-page recipe — see var/templates/README.md):
 *
 *  - **Field definition** — `init()` declares the guest-info inputs with
 *    the EC-CUBE field names / placeholders so the rendered `<input>` /
 *    `<select>` markup reproduces EC-CUBE's `ec-*` form. EC-CUBE's
 *    `NonMemberType` nests fields under compound types (`form.name.name01`,
 *    `form.address.pref`, `form.email.first`), but BeMart's NonMember
 *    resource body carries the fields FLAT (`name01`, `pref`, `email`,
 *    ...) — exactly the params SubmitNonMemberInput accepts — so the form
 *    declares them flat. The names are the same leaf names EC-CUBE renders.
 *  - **HTML rendering** — `{{ form.input('name01') }}` in
 *    `Page/Shopping/NonMember.html.twig`.
 *  - **Repopulation** — {@see fillValues()} re-shows submitted values
 *    after a Be-domain rejection.
 *
 * What this class deliberately does NOT do — **validation authority**:
 *
 *   BeMart validates the guest info in the domain via Be Framework
 *   Semantics (SubmitNonMemberInput) and the Final/exception layer. Those
 *   ALPS-derived rules are the single source of truth. Duplicating them
 *   into Aura.Filter would drift from the spec, so the filter here carries
 *   only NON-AUTHORITATIVE structural checks. The `#[FormValidation]`
 *   aspect is NOT used.
 *
 * @link https://schema.org/PostalAddress
 */
final class NonMemberForm extends AbstractForm
{
    /** @var array<int|string, string> */
    private const PREF_OPTIONS = [
        '' => '',
        '1' => '北海道',
        '2' => '青森県',
        '3' => '岩手県',
        '4' => '宮城県',
        '5' => '秋田県',
        '6' => '山形県',
        '7' => '福島県',
        '8' => '茨城県',
        '9' => '栃木県',
        '10' => '群馬県',
        '11' => '埼玉県',
        '12' => '千葉県',
        '13' => '東京都',
        '14' => '神奈川県',
        '15' => '新潟県',
        '16' => '富山県',
        '17' => '石川県',
        '18' => '福井県',
        '19' => '山梨県',
        '20' => '長野県',
        '21' => '岐阜県',
        '22' => '静岡県',
        '23' => '愛知県',
        '24' => '三重県',
        '25' => '滋賀県',
        '26' => '京都府',
        '27' => '大阪府',
        '28' => '兵庫県',
        '29' => '奈良県',
        '30' => '和歌山県',
        '31' => '鳥取県',
        '32' => '島根県',
        '33' => '岡山県',
        '34' => '広島県',
        '35' => '山口県',
        '36' => '徳島県',
        '37' => '香川県',
        '38' => '愛媛県',
        '39' => '高知県',
        '40' => '福岡県',
        '41' => '佐賀県',
        '42' => '長崎県',
        '43' => '熊本県',
        '44' => '大分県',
        '45' => '宮崎県',
        '46' => '鹿児島県',
        '47' => '沖縄県',
    ];

    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the non-member purchaser form fields.
     *
     * Field names / placeholders are ported from EC-CUBE's `NonMemberType`
     * leaf fields (`NameType` / `KanaType` / `PostalType` / `AddressType` /
     * `PhoneNumberType` / `RepeatedEmailType`) + the template's
     * `form_widget` `attr` options. `pref` is backed by EC-CUBE master
     * data (`mtb_pref`); BeMart carries the default fixture option set
     * locally so the browser form can be submitted end to end.
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
        $this->setField('pref', 'select')->setOptions(self::PREF_OPTIONS);
        $this->setField('addr01', 'text')
            ->setAttribs(['placeholder' => '市区町村名(例：大阪市北区)']);
        $this->setField('addr02', 'text')
            ->setAttribs(['placeholder' => '番地・ビル名(例：西梅田1丁目6-8)']);

        // 電話番号.
        $this->setField('phoneNumber', 'text');

        // メールアドレス + 確認 (RepeatedEmailType — first / second).
        $this->setField('email', 'text')
            ->setAttribs(['placeholder' => '例：ec-cube@example.com']);
        $this->setField('email_confirm', 'text')
            ->setAttribs(['placeholder' => '確認のためもう一度入力してください']);

        // NON-AUTHORITATIVE structural checks only — authority is the Be
        // domain (SubmitNonMemberInput Semantics).
        $this->filter->validate('name01')->isNotBlank();
        $this->filter->validate('name02')->isNotBlank();
        $this->filter->validate('postalCode')->isNotBlank();
        $this->filter->validate('email')->isNotBlank();
    }

    /**
     * Repopulates the form inputs with values.
     *
     * Used to re-show submitted values after a Be-domain rejection.
     *
     * @param array<string, scalar|null> $values field name => value
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }

    /**
     * Bridges a Be-domain rejection onto the form's error state.
     *
     * Validation authority stays with Be — this method only transports
     * the verdict.
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
