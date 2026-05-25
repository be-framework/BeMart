<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

use function range;

/**
 * EC-CUBE goCustomerRegistration の会員登録フォーム — Ray.WebFormModule.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Front/EntryType` + the `Entry/index.twig`
 * `form_widget` calls. EC-CUBE renders these inputs through the Symfony
 * FormView; BeMart renders them through Ray.WebFormModule (Aura.Input +
 * Aura.Filter + Aura.Html).
 *
 * Role of this class (the form-page recipe — see var/templates/README.md):
 *
 *  - **Field definition** — `init()` declares the customer-registration
 *    inputs with the EC-CUBE field names / ids / attributes so the
 *    rendered `<input>` / `<select>` markup reproduces EC-CUBE's `ec-*`
 *    form. EC-CUBE's `EntryType` nests fields under compound types
 *    (`form.name.name01`), but BeMart's Entry resource body carries the
 *    fields FLAT (`name01`, `email`, ...) — see Entry::onGet — so the
 *    form declares them flat to match the resource. The names are the
 *    same leaf names EC-CUBE renders.
 *  - **HTML rendering** — `{{ form.input('name01') }}` in
 *    `Entry.html.twig`.
 *  - **Repopulation** — after a failed POST the resource calls
 *    {@see fillValues()} so the page re-renders with the entered values.
 *
 * What this class deliberately does NOT do — **validation authority**:
 *
 *   BeMart validates registration in the domain via Be Framework
 *   Semantics (the ALPS-derived rules on RegisterCustomerInput) and the
 *   Final/exception layer (EmailAlreadyRegisteredException). Those rules
 *   are the single source of truth. Duplicating them into Aura.Filter
 *   would drift from the spec, so the filter here carries only
 *   NON-AUTHORITATIVE structural checks (required / blank) for a future
 *   fast-UX pre-check. The Entry resource never consults the filter
 *   verdict: it hands the raw input to the Becoming chain and, on
 *   rejection, bridges the domain error onto this form via
 *   {@see setDomainError()}. The `#[FormValidation]` aspect is NOT used.
 *
 * @link https://schema.org/RegisterAction
 */
final class EntryForm extends AbstractForm
{
    /** @var array<string, string> */
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

    /** @var array<string, string> */
    private const SEX_OPTIONS = [
        '1' => '男性',
        '2' => '女性',
    ];

    /** @var array<string, string> */
    private const JOB_OPTIONS = [
        '' => '',
        '1' => '公務員',
        '2' => 'コンサルタント',
        '3' => 'コンピュータ関連技術職',
        '4' => 'コンピュータ関連以外の技術職',
        '5' => '金融関係',
        '6' => '医師',
        '7' => '弁護士',
        '8' => '総務・人事・事務',
        '9' => '営業・販売',
        '10' => '研究・開発',
        '11' => '広報・宣伝',
        '12' => '企画・マーケティング',
        '13' => 'デザイン関係',
        '14' => '会社経営・役員',
        '15' => '出版・マスコミ関係',
        '16' => '学生・フリーター',
        '17' => '主婦',
        '18' => 'その他',
    ];

    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name. Populated by {@see setDomainError()}; consulted by
     * {@see error()} so `{{ form.error(field) }}` shows the Be-domain
     * message, not an Aura.Filter message.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the customer-registration form fields.
     *
     * Field names / placeholders are ported from EC-CUBE's `EntryType`
     * leaf fields + `Entry/index.twig`'s `form_widget` `attr` options so
     * the rendered markup carries EC-CUBE's `ec-*` form shape. `pref` /
     * `birth_*` / `sex` / `job` are backed by EC-CUBE master data. BeMart
     * now carries the default fixture option sets locally so the browser UI
     * is usable, while the authoritative validation remains in the Be
     * domain.
     */
    #[Override]
    public function init(): void
    {
        // お名前 / お名前(カナ) — half-width name pairs.
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

        // メールアドレス + 確認.
        $this->setField('email', 'text')
            ->setAttribs(['placeholder' => '例：ec-cube@example.com']);
        $this->setField('email_confirm', 'text')
            ->setAttribs(['placeholder' => '確認のためもう一度入力してください']);

        // パスワード + 確認.
        $this->setField('password', 'password')
            ->setAttribs(['placeholder' => '半角英数記号8〜32文字']);
        $this->setField('password_confirm', 'password')
            ->setAttribs(['placeholder' => '確認のためもう一度入力してください']);

        // 生年月日 — three selects. Demo/browser-smoke default keeps the
        // registration path one-click-testable while users can still change
        // the values before submitting.
        $this->setField('birth_year', 'select')->setOptions(self::yearOptions())->setValue('1991');
        $this->setField('birth_month', 'select')->setOptions(self::numberOptions(1, 12))->setValue('8');
        $this->setField('birth_day', 'select')->setOptions(self::numberOptions(1, 31))->setValue('1');

        // 性別 — radio. Same demo/browser-smoke default policy as birth.
        $this->setField('sex', 'radio')->setOptions(self::SEX_OPTIONS)->setValue('1');

        // 職業 — select.
        $this->setField('job', 'select')->setOptions(self::JOB_OPTIONS);

        // 利用規約同意 — checkbox.
        $this->setField('user_policy_check', 'checkbox');

        // NON-AUTHORITATIVE structural checks only. The authoritative
        // format / required rules live in the Be domain
        // (RegisterCustomerInput Semantics + CustomerRegistered Final).
        $this->filter->validate('name01')->isNotBlank();
        $this->filter->validate('name02')->isNotBlank();
        $this->filter->validate('email')->isNotBlank();
        $this->filter->validate('password')->isNotBlank();
    }

    /**
     * Repopulates the form inputs with submitted values.
     *
     * Called by the Entry resource after a failed POST so the page
     * re-renders with the entered values. The password fields are
     * intentionally NOT repopulated; pass only the safe-to-echo values.
     *
     * @param array<string, string> $values field name => submitted value
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }

    /**
     * Bridges a Be-domain rejection onto the form's error state.
     *
     * The Entry resource calls this when the Becoming chain rejects the
     * input (a SemanticVariableException for malformed input, or an
     * EmailAlreadyRegisteredException for a duplicate email). Validation
     * authority stays with Be — this method only transports the verdict.
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

    /** @return array<string, string> */
    private static function yearOptions(): array
    {
        /** @var array<string, string> $options */
        $options = ['' => ''];
        foreach (range(2026, 1926) as $year) {
            $options[(string) $year] = (string) $year;
        }

        return $options;
    }

    /** @return array<string, string> */
    private static function numberOptions(int $start, int $end): array
    {
        $options = ['' => ''];
        foreach (range($start, $end) as $number) {
            $options[(string) $number] = (string) $number;
        }

        return $options;
    }
}
