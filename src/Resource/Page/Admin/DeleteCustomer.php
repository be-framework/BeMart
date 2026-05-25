<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\CustomerNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\AdminCustomerDeleted;
use MyVendor\BeMart\Be\Input\AdminDeleteCustomerInput;

use function assert;

/**
 * EC-CUBE doDeleteCustomer — 会員を削除する (管理画面).
 *
 * Admin-side counterpart of Wave 2G's mypage WithdrawResource. The
 * resource is the HTTP entry point: builds AdminDeleteCustomerInput,
 * hands it to Becoming, and projects the resulting AdminCustomerDeleted
 * into the response body. CSRF is enforced — this is a state-changing
 * operation.
 *
 * ALPS doc: 会員を物理削除する。受注は会員IDをNULLにして保持。
 * Despite the "物理削除" wording, EC-CUBE 4.x preserves the row for FK
 * integrity (customer_status flips to 3 + email rewritten with a dummy);
 * the per-order customerId-NULLing cascade is OUT OF SCOPE here — see
 * the AdminCustomerDeleted Final's docblock.
 *
 * Method choice — POST not DELETE: BEAR has no natural "DELETE by-id-
 * in-body" pattern (DELETE would put the id in the URL, but admin
 * tooling supplies it via a form click on the customer-list row). POST
 * with a CSRF token keeps the resource shape consistent with the rest
 * of the admin Page\Admin\... surface (CreateCustomer, Logout).
 *
 * Failure mapping (cross-firewall AUTHZ → existence ladder):
 *   - Invalid CSRF                       → 403 (token missing / bad)
 *   - SemanticVariableException          → 400 (customerId format)
 *   - UnauthorizedAdminAccessException   → 403 (no admin session)
 *   - CustomerNotFoundException          → 404 (no such customerId)
 *
 * Success (200): `{customerId, originalEmail, alreadyDeleted, message}`.
 * The `alreadyDeleted` flag distinguishes a fresh delete (false, mail
 * sent) from an idempotent replay (true, no mail) — same shape as the
 * pilot's idempotent re-add convention.
 *
 * Anti-enumeration: the 403 / 404 ordering matches the Be Final's
 * check sequence (AUTHZ first, existence second). An admin-anonymous
 * client learns NOTHING about which customerIds resolve — same
 * discipline as goCustomer (Wave 5N).
 */
class DeleteCustomer extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
    ) {
    }

    /**
     * Wave 6: customerId is user-controlled input from the admin UI
     * (admin clicks a customer-list row, the row's customerId feeds
     * this form). Same taint discipline as goCustomer's email.
     *
     * @psalm-taint-source input $customerId
     */
    #[Link(rel: 'goCustomerList', href: 'page://self/admin/customer-list')]
    #[CsrfProtected]
    public function onPost(
        string $customerId,
    ): static {
        try {
            $final = ($this->becoming)(new AdminDeleteCustomerInput(
                customerId: $customerId,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = [
                'message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.',
            ];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (CustomerNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された会員は見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof AdminCustomerDeleted);

        $this->code = Code::OK;
        $this->body = [
            'customerId' => $final->customerId,
            'originalEmail' => $final->originalEmail,
            'alreadyDeleted' => $final->alreadyDeleted,
            'message' => $final->alreadyDeleted
                ? '指定された会員は既に削除されています。'
                : '会員を削除しました。',
        ];

        return $this;
    }
}
