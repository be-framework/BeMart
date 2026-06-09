<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use MyVendor\BeMart\Annotation\CsrfProtected;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\MasterOperationNotSupportedException;
use MyVendor\BeMart\Be\Exception\MasterRowNotFoundException;
use MyVendor\BeMart\Be\Exception\UnauthorizedAdminAccessException;
use MyVendor\BeMart\Be\Final\VisibleToggled;
use MyVendor\BeMart\Be\Input\ToggleVisibleInput;
use BEAR\Resource\Annotation\JsonSchema;

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
    ) {
    }

    /**
     * ALPS `doToggleVisible` に対応する PUT 操作。
     * @psalm-taint-source input $masterType
     * @psalm-taint-source input $rowId
     * @psalm-taint-source input $visible
     */
    #[Alps('doToggleVisible')]
    #[JsonSchema(schema: 'put-admin-toggle-visible.json', params: 'put-admin-toggle-visible.param.json')]
    #[CsrfProtected]
    public function onPut(
        string $masterType,
        string $rowId,
        bool $visible,
    ): static {
        $final = ($this->becoming)(new ToggleVisibleInput(
            masterType: $masterType,
            rowId: $rowId,
            visible: $visible,
        ));

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
