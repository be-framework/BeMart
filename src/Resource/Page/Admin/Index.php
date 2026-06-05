<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;

/**
 * EC-CUBE admin home — 管理画面ダッシュボード (top-level wave, Phase 3).
 *
 * Thin renderer for the admin dashboard (`admin/index.twig`). EC-CUBE's
 * dashboard is a controller-assembled aggregate of KPIs — order-status
 * counts, weekly/monthly/yearly sales charts, shop-status counters
 * (out-of-stock / product / customer totals) and a recommended-plugins
 * panel. None of those projections exist as a Be Framework domain
 * transition: there is no `goDashboard` in `alps.json` and no dashboard
 * Entity. Authoring one would mean inventing data, which the 厳密移植
 * discipline forbids.
 *
 * So this resource is a THIN RENDERER only: it enforces the admin
 * firewall (same 403-when-anonymous contract as every other admin page —
 * the html context binds the anonymous `FakeAdminSession(null)` by
 * default) and exposes a body whose dashboard-widget fields are present
 * but EMPTY / zero. The HTML port (`Index.html.twig`) renders the
 * EC-CUBE dashboard skeleton verbatim around those empty values; the
 * widgets that would be data-driven are enumerated as render-diff
 * residual.
 *
 * MISSING-BODY-FIELD follow-ups (flagged, NOT enriched here — the brief
 * forbids inventing Entities): the dashboard needs `orderStatuses`,
 * `orders` (per-status counts), `salesThisMonth` / `salesToday` /
 * `salesYesterday`, `countNonStockProducts`, `countProducts`,
 * `countCustomers` and `recommendedPlugins`. Each requires a real Be
 * domain projection (an admin dashboard `goDashboard` transition over
 * the order / product / customer / plugin storages). Until those land
 * the body carries safe empties so the page still renders.
 */
class Index extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
    ) {
    }

    /**
     * Renders the admin dashboard scaffolding.
     *
     * Admin-only: returns 403 for an anonymous (not-logged-in-as-admin)
     * request — the same firewall contract as the News / Customer admin
     * pages, enforced here at the resource layer because there is no Be
     * Final to raise `UnauthorizedAdminAccessException`.
     */
    #[Link(rel: 'goMemberList', href: 'page://self/admin/member-list')]
    #[Link(rel: 'goContentCache', href: 'page://self/admin/content/cache')]
    #[Link(rel: 'doAdminLogout', href: 'page://self/admin/logout', method: 'post')]
    #[Link(rel: 'goAdminLogout', href: 'page://self/admin/login', method: 'post')]
    public function onGet(): static
    {
        if ($this->adminSession->adminId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $this->code = Code::OK;
        // Dashboard-widget placeholders — see the class doc's
        // MISSING-BODY-FIELD follow-up list. Present so the ported
        // template renders; empty because no Be projection feeds them.
        $this->body = [
            'orderStatuses' => [],
            'orders' => [],
            'salesThisMonth' => null,
            'salesToday' => null,
            'salesYesterday' => null,
            'countNonStockProducts' => 0,
            'countProducts' => 0,
            'countCustomers' => 0,
            'recommendedPlugins' => [],
        ];

        return $this;
    }
}
