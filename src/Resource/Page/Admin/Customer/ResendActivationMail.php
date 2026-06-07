<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin\Customer;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\CustomerAlreadyActivatedException;
use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\ActivationMailResent;
use MyVendor\BeMart\Be\Input\ResendActivationMailInput;
use BEAR\Resource\Annotation\JsonSchema;

use function assert;

/**
 * EC-CUBE doResendActivationMail — 認証メールを再送する (Phase 3
 * ALPS-audit remediation).
 *
 *   POST /admin/customer/resend-activation-mail
 *
 * From the admin customer-list screen an ADMIN resends the email-
 * verification (full-registration) mail to a 仮会員 (provisional
 * customer) who never followed the original activation link. Derived
 * from EC-CUBE's `admin_customer_resend` route. The mail carries an
 * activation URL embedding the customer's `secretKey`; the customer
 * later promotes to a full member via `doActivateCustomer`. ALPS marks
 * it `unsafe` — POST is the matching verb, each call sends a fresh mail.
 *
 * Failure mapping (cross-firewall AUTHZ → existence → state ladder):
 *   - Invalid CSRF                          → 403
 *   - SemanticVariableException             → 400 (email format)
 *   - UnauthorizedAdminAccessException      → 403 (no admin session)
 *   - CustomerNotFoundException             → 404 (no such email)
 *   - CustomerAlreadyActivatedException     → 409 (not a 仮会員)
 *
 * The 403-before-404 ordering matches the Be Final's check sequence —
 * an admin-anonymous client learns NOTHING about which emails resolve
 * (same anti-enumeration discipline as goCustomer).
 */
class ResendActivationMail extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * ALPS `doResendActivationMail` に対応する POST 操作。
     * @psalm-taint-source input $email
     */
    #[Alps('doResendActivationMail')]
    #[JsonSchema(schema: 'post-admin-customer-resend-activation-mail.json', params: 'post-admin-customer-resend-activation-mail.param.json')]
    #[Link(rel: 'goCustomer', href: 'page://self/admin/customer', method: 'get')]
    #[CsrfProtected]
    public function onPost(
        string $email,
    ): static {
        try {
            $final = ($this->becoming)(new ResendActivationMailInput(email: $email));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (CustomerNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された会員は見つかりませんでした。'];

            return $this;
        } catch (CustomerAlreadyActivatedException) {
            // 409 Conflict — BEAR\Resource\Code has no CONFLICT constant;
            // the literal matches the rest of the admin surface
            // (CreateCustomer, Member, Product all use a bare 409).
            $this->code = 409;
            $this->body = ['message' => '指定された会員は既に本会員です。'];

            return $this;
        }

        assert($final instanceof ActivationMailResent);

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'email' => $final->email,
            'message' => '認証メールを再送しました。',
        ];

        return $this;
    }
}
