<?php

/**
 * Bind parameters for Koriym.SqlQuality analysis of var/sql/.
 *
 * Each key is a SQL file under var/sql/; each value is the `:placeholder => value`
 * map used to interpolate the query before EXPLAIN. This file is also the SCOPE
 * lever: SqlFileAnalyzer iterates these keys, so only the listed files are analyzed.
 *
 * Scope: the 76 single-statement SELECT (read) queries. Command files
 * (INSERT/UPDATE/DELETE) and multi-statement scripts (cart_save, order_item_register,
 * plugin_set_enabled, tmail_template_update, …) are intentionally excluded — they
 * cannot be EXPLAIN-analyzed safely and would execute writes under EXPLAIN ANALYZE.
 *
 * Values target the data in sql/seed/analysis-sample.sql (2,000 products /
 * product_classes, 500 customers, 5 admins) so EXPLAIN ANALYZE returns rows.
 * Order/cart/config tables are unseeded; those queries still EXPLAIN validly
 * (returning 0 rows) — the placeholder values only need a valid type.
 *
 * @see https://github.com/koriym/Koriym.SqlQuality
 */

declare(strict_types=1);

return [
    // ── id allocators (no params) ────────────────────────────────────────────
    'address_next_id.sql' => [],
    'admin_next_id.sql' => [],
    'block_next_id.sql' => [],
    'category_next_id.sql' => [],
    'classCategory_next_id.sql' => [],
    'className_next_id.sql' => [],
    'customer_next_id.sql' => [],
    'delivery_next_id.sql' => [],
    'news_next_id.sql' => [],
    'page_next_id.sql' => [],
    'paymentMethodAdmin_next_id.sql' => [],
    'tag_next_id.sql' => [],
    'taxRule_next_id.sql' => [],

    // ── catalog (seeded) ─────────────────────────────────────────────────────
    'product_get.sql' => ['productCode' => 'CODE000001'],
    'product_class_get.sql' => ['productCode' => 'CODE000001'],
    'product_list.sql' => ['limit' => 20, 'offset' => 0],
    'product_search.sql' => ['nameKeyword' => '商品', 'limit' => 20],
    'product_export.sql' => [],
    'tag_get.sql' => ['tagId' => 1],
    'tag_list.sql' => [],

    // ── customer / address / favorite (seeded customers) ─────────────────────
    'customer_find_by_email.sql' => ['email' => 'user1@example.com'],
    'customer_email_exists.sql' => ['email' => 'user1@example.com'],
    'customer_find_by_id.sql' => ['customerId' => 1],
    'customer_find_by_secret_key.sql' => ['secretKey' => hash('sha256', 'secret1')],
    'customer_search.sql' => ['nameKeyword' => '姓', 'emailKeyword' => 'example', 'limit' => 20],
    'address_get.sql' => ['addressId' => 1],
    'address_list_by_customer.sql' => ['customerId' => 1],
    'favorite_has.sql' => ['customerId' => 1, 'productCode' => 'CODE000001'],
    'favorite_list.sql' => ['customerId' => 1],
    'password_reset_get.sql' => ['resetKey' => 'reset-key-sample'],

    // ── admin / member (seeded admins) ───────────────────────────────────────
    'admin_find_by_id.sql' => ['adminId' => 1],
    'admin_find_by_login.sql' => ['loginId' => 'admin1'],
    'admin_two_factor_secret.sql' => ['loginId' => 'admin1'],
    'admin_list.sql' => ['limit' => 20, 'offset' => 0],
    'admin_search.sql' => ['nameKeyword' => '管理者'],

    // ── order / shipping (unseeded → 0 rows, still EXPLAIN-valid) ─────────────
    'order_by_order_no.sql' => ['orderNo' => 'ORDER-0001'],
    'order_by_pre_order_id.sql' => ['preOrderId' => 'PRE-0001'],
    'order_history_by_order_no.sql' => ['orderNo' => 'ORDER-0001'],
    'order_items_by_order_no.sql' => ['orderNo' => 'ORDER-0001'],
    'order_list_all.sql' => ['limit' => 20, 'offset' => 0],
    'order_list_by_customer.sql' => ['customerId' => 1, 'limit' => 20, 'offset' => 0],
    'shipping_get_by_order_no.sql' => ['orderNo' => 'ORDER-0001'],
    'shipping_tracking_by_order_no.sql' => ['orderNo' => 'ORDER-0001'],
    'shipping_list_all.sql' => [],

    // ── cart (unseeded) ──────────────────────────────────────────────────────
    'cart_by_key.sql' => ['cartKey' => 'sample-session_1'],
    'cart_by_session_prefix.sql' => ['sessionPrefix' => 'sample-session'],

    // ── plugin / csv ─────────────────────────────────────────────────────────
    'plugin_find_by_code.sql' => ['code' => 'SamplePlugin'],
    'plugin_list_all.sql' => [],
    'csv_column_list_by_type.sql' => ['csvType' => 1],

    // ── admin config: t* read queries (mostly unseeded → 0 rows) ─────────────
    'tbase_info_get.sql' => [],
    'ttrade_law_get.sql' => [],
    'ttemplate_list.sql' => [],
    'tblock_get.sql' => ['blockId' => 1],
    'tblock_list.sql' => [],
    'tcategory_get.sql' => ['categoryId' => 1],
    'tcategory_list.sql' => [],
    'tclass_category_get.sql' => ['classCategoryId' => 1],
    'tclass_category_list.sql' => [],
    'tclass_category_list_by_class_name.sql' => ['classNameId' => 1],
    'tclass_name_get.sql' => ['classNameId' => 1],
    'tclass_name_list.sql' => [],
    'tdelivery_get.sql' => ['deliveryId' => 1],
    'tdelivery_list.sql' => [],
    'tlayout_get.sql' => ['layoutId' => 1],
    'tlayout_list.sql' => [],
    'tlogin_history_list.sql' => ['limit' => 20],
    'tmail_template_get.sql' => ['mailTemplateId' => 1],
    'tmail_template_list.sql' => [],
    'tnews_get.sql' => ['newsId' => 1],
    'tnews_list.sql' => [],
    'tpage_get.sql' => ['pageId' => 1],
    'tpage_list.sql' => [],
    'tpayment_get.sql' => ['paymentId' => 1],
    'tpayment_list.sql' => [],
    'ttax_rule_get.sql' => ['taxRuleId' => 1],
    'ttax_rule_list.sql' => [],
];
