<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Query\DashboardCountsQueryInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use BEAR\Resource\Annotation\JsonSchema;

/**
 * EC-CUBE admin home — 管理画面ダッシュボード (top-level wave, Phase 3).
 *
 * Renderer for the admin dashboard (`admin/index.twig`). EC-CUBE's
 * dashboard is a controller-assembled aggregate of KPIs — order-status
 * counts, weekly/monthly/yearly sales charts, shop-status counters
 * (out-of-stock / product / customer totals) and a recommended-plugins
 * panel.
 *
 * The 「ショップ状況」 counters (取扱商品数 / 会員数 / 在庫切れ商品数) ARE
 * wired to a real projection: {@see DashboardCountsQueryInterface} reads
 * them in one query over the product / customer / product-class storages.
 * Counting registered rows is not inventing data, so these are surfaced
 * honestly.
 *
 * The remaining widgets — `orderStatuses`, `orders` (per-status counts),
 * `salesThisMonth` / `salesToday` / `salesYesterday` and
 * `recommendedPlugins` — have no Be Framework projection yet (no
 * `goDashboard` transition / sales-aggregate Entity in `alps.json`), so
 * the body still carries safe empties for them and the HTML port renders
 * the EC-CUBE skeleton verbatim around those.
 */
class Index extends ResourceObject
{
    public function __construct(
        private readonly AdminSession $adminSession,
        private readonly DashboardCountsQueryInterface $dashboardCounts,
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
    #[Alps('goAdminTop')]
    #[JsonSchema(schema: 'get-admin-index.json')]
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
        // ショップ状況: real counts over product / customer / product-class
        // storage. The remaining widgets stay empty — see the class doc.
        $row = $this->dashboardCounts->counts()[0] ?? [];
        $this->body = [
            'orderStatuses' => [],
            'orders' => [],
            'salesThisMonth' => null,
            'salesToday' => null,
            'salesYesterday' => null,
            'countNonStockProducts' => (int) ($row['nonStock'] ?? 0),
            'countProducts' => (int) ($row['products'] ?? 0),
            'countCustomers' => (int) ($row['customers'] ?? 0),
            'recommendedPlugins' => [],
        ];

        return $this;
    }
}
