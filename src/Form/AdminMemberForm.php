<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE メンバー登録/編集フォーム — Setting/System section, form-page recipe.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/MemberType` + the
 * `admin/Setting/System/member_edit.twig` `form_widget` calls. EC-CUBE
 * renders these inputs through the Symfony FormView; BeMart renders them
 * through Ray.WebFormModule (Aura.Input + Aura.Filter + Aura.Html) — the
 * same library the storefront's {@see LoginForm} pilot and the admin
 * pilot's {@see AdminNewsForm} adopted.
 *
 * Same recipe as {@see AdminNewsForm} (see var/templates/README.md): the
 * form is a FIELD-DEFINITION + RENDERER only — VALIDATION AUTHORITY
 * STAYS WITH the Be Framework Becoming chain. The Member resource hands
 * the raw input to Becoming; on a rejection it bridges the verdict onto
 * this form (repopulated values + inline error). The `#[FormValidation]`
 * aspect is NOT used.
 *
 * Field names / ids are ported from EC-CUBE's `MemberType` (block prefix
 * `admin_member`, so the FormView ids are `admin_member_<field>`).
 *
 * Body-field gaps — the MemberFetched projection (Wave 8 admin-detail
 * slice) carries only `loginId` / `name` / `authority` / `work`; it does
 * NOT carry `department` or the 2FA columns, and `authority` / `work`
 * are bare ints with no joined mtb_authority / mtb_work option set.
 * {@see fillValues()} therefore repopulates only `name` and `loginId`;
 * the `department` / `Authority` / `Work` / `twoFactorAuthEnabled`
 * controls render with empty values. FLAGGED missing-body-fields.
 */
final class AdminMemberForm extends AbstractForm
{
    /** @var array<int|string, string> */
    private const AUTHORITY_OPTIONS = [
        '0' => 'システム管理者',
        '1' => '店舗オーナー',
    ];

    /**
     * Domain errors bridged in from the Be Becoming chain, keyed by
     * field name.
     *
     * @var array<string, string>
     */
    private array $domainErrors = [];

    /**
     * Declares the member form fields.
     *
     * Ported from EC-CUBE's `MemberType::buildForm()` + `member_edit.twig`:
     * `name` (text), `department` (text), `loginId` (text),
     * `plain_password` (repeated → first/second password inputs),
     * `Authority` (entity select), `Work` (radio), and
     * `twoFactorAuthEnabled` (checkbox). EC-CUBE's block prefix is
     * `admin_member`, so the rendered ids are `admin_member_<field>`.
     */
    #[Override]
    public function init(): void
    {
        $this->setField('name', 'text')
            ->setAttribs([
                'id' => 'admin_member_name',
                'class' => 'form-control',
            ]);

        $this->setField('department', 'text')
            ->setAttribs([
                'id' => 'admin_member_department',
                'class' => 'form-control',
                'disabled' => 'disabled',
            ]);

        $this->setField('loginId', 'text')
            ->setAttribs([
                'id' => 'admin_member_loginId',
                'class' => 'form-control',
            ]);

        $this->setField('password', 'password')
            ->setAttribs([
                'id' => 'admin_member_password',
                'class' => 'form-control',
            ]);

        $this->setField('passwordConfirm', 'password')
            ->setAttribs([
                'id' => 'admin_member_passwordConfirm',
                'class' => 'form-control',
            ]);

        $this->setField('authority', 'select')
            ->setAttribs([
                'id' => 'admin_member_authority',
                'class' => 'form-select',
            ])
            ->setOptions(self::AUTHORITY_OPTIONS)
            ->setValue('1');

        $this->setField('twoFactorAuthEnabled', 'checkbox')
            ->setAttribs([
                'id' => 'admin_member_twoFactorAuthEnabled',
                'disabled' => 'disabled',
            ])
            ->setOptions(['1' => '2段階認証']);

        // NON-AUTHORITATIVE structural checks only. The authoritative
        // name / login-id / password rules live in the Be domain
        // (CreateMemberInput / UpdateMemberInput Semantics). The Member
        // resource does not consult this filter.
        $this->filter->validate('name')->isNotBlank();
        $this->filter->validate('loginId')->isNotBlank();
    }

    /**
     * Repopulates the form inputs from the Member resource body.
     *
     * Maps the resource body keys (`name`, `loginId`) onto the EC-CUBE
     * form field names. The `department` / `Authority` / `Work` /
     * `twoFactorAuthEnabled` controls have no body source (the
     * MemberFetched projection does not carry them — FLAGGED), so they
     * render empty. The password inputs are always blank on edit.
     *
     * @param array<string, mixed> $body the Member resource body
     */
    public function fillValues(array $body): void
    {
        $this->fill([
            'name' => (string) ($body['name'] ?? ''),
            'loginId' => (string) ($body['loginId'] ?? ''),
            'authority' => (string) ($body['authority'] ?? '1'),
        ]);
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
