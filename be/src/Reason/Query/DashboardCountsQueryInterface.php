<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Query;

use Ray\MediaQuery\Annotation\DbQuery;

/**
 * Admin dashboard ショップ状況 counters.
 *
 * One read returning a single row with the three shop-status counts the
 * EC-CUBE dashboard shows under 「ショップ状況」: 取扱商品数 (products),
 * 会員数 (active customers, customer_status_id = 2) and 在庫切れ商品数
 * (product classes with finite stock at or below zero). These are real
 * projections over existing storage — counting registered rows, not
 * inventing data — so they are legitimate to surface on the thin
 * dashboard renderer.
 */
interface DashboardCountsQueryInterface
{
    /**
     * @return list<array{products: int|string, customers: int|string, nonStock: int|string}>
     */
    #[DbQuery('dashboard_counts')]
    public function counts(): array;
}
