<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;

/**
 * EC-CUBE admin プラグイン拡張用スロット — top-level wave, Phase 3.
 *
 * Thin renderer for `admin/empty_page.twig` — EC-CUBE's near-empty
 * `{% extends default_frame %}` stub. It carries no content of its own;
 * it exists as a routable extension SLOT that plugins fill via template
 * events. The fan-out plan (`docs/phases/admin-fanout-plan.md`) lists it
 * as a borderline page kept as a trivial port.
 *
 * No domain logic, no body data: the resource enforces the admin
 * firewall (so the slot is admin-only, like every other admin page) and
 * renders the admin frame with an empty `main` block. There is nothing
 * to enrich and no missing-body-field — the page IS empty by design.
 */
class EmptyPage extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
    ) {
    }

    /**
     * Renders the empty admin extension-slot page.
     *
     * Admin-only: returns 403 for an anonymous request — the same
     * firewall contract as the other admin pages.
     */
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        $this->body = [];

        return $this;
    }
}
