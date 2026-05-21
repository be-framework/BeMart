<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\MasterOperationNotSupportedException;
use MyVendor\BeMart\Be\Exception\MasterRowNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\VisibleToggled;
use MyVendor\BeMart\Be\Input\ToggleVisibleInput;
use MyVendor\BeMart\Be\Reason\Service\CsrfTokenInterface;

use function assert;

/**
 * EC-CUBE doToggleVisible — 表示・非表示を切り替える (Phase 3
 * ALPS-audit remediation).
 *
 *   PUT /admin/toggle-visible
 *
 * The generic admin-list visibility transition. EC-CUBE has a
 * per-master *_visible / *_visibility route for each list screen
 * (Payment / Delivery / ClassCategory / News); BeMart folds them into
 * this one resource keyed by `masterType`. ALPS marks it `idempotent`
 * — the flag is set to an explicit `visible` value, so PUT is the verb.
 *
 * Failure mapping:
 *   - Invalid CSRF                            → 403
 *   - SemanticVariableException               → 400 (masterType / visible)
 *   - UnauthorizedAdminAccessException        → 403 (no admin session)
 *   - MasterRowNotFoundException              → 404
 *   - MasterOperationNotSupportedException    → 400 (master lacks visible)
 */
class ToggleVisible extends ResourceObject
{
    public function __construct(
        private readonly BecomingInterface $becoming,
        private readonly CsrfTokenInterface $csrf,
    ) {
    }

    /**
     * @psalm-taint-source input $masterType
     * @psalm-taint-source input $rowId
     * @psalm-taint-source input $visible
     * @psalm-taint-source input $csrfToken
     */
    public function onPut(
        string $masterType,
        string $rowId,
        bool $visible,
        string|null $csrfToken = null,
    ): static {
        if (! $this->csrf->isValid($csrfToken)) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Invalid or missing CSRF token.'];

            return $this;
        }

        try {
            $final = ($this->becoming)(new ToggleVisibleInput(
                masterType: $masterType,
                rowId: $rowId,
                visible: $visible,
            ));
        } catch (SemanticVariableException $e) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => $e->getErrors()->getMessages('ja')[0] ?? 'Invalid input.'];

            return $this;
        } catch (MasterOperationNotSupportedException) {
            $this->code = Code::BAD_REQUEST;
            $this->body = ['message' => 'このマスタは表示・非表示の切り替えに対応していません。'];

            return $this;
        } catch (UnauthorizedAdminAccessException) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        } catch (MasterRowNotFoundException) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => '指定された行は見つかりませんでした。'];

            return $this;
        }

        assert($final instanceof VisibleToggled);

        $this->code = Code::OK;
        $this->body = [
            'masterType' => $final->masterType,
            'rowId' => $final->rowId,
            'visible' => $final->visible,
        ];

        return $this;
    }
}
