-- EC-CUBE system master rows required after schema + mtb_* initialization.
--
-- These dtb_* rows are application configuration masters, not Web-created
-- business data such as products, customers, carts, orders, shippings, or
-- favorites. They provide the stock admin affordance targets that EC-CUBE
-- expects to exist in a freshly installed shop.

-- Ensure UTF-8 so Japanese master values (payment method names, admin name, …)
-- load correctly regardless of the mysql client's default charset — mtb-master.sql
-- does the same. Without this, a latin1-default client double-encodes them
-- (e.g. クレジットカード → ã‚¯ãƒ¬ã‚¸ãƒƒãƒˆã‚«ãƒ¼ãƒ‰).
SET NAMES utf8mb4;

INSERT INTO `dtb_member`
    (`id`, `work_id`, `authority_id`, `creator_id`, `name`, `department`, `login_id`, `password`, `salt`, `sort_no`, `two_factor_auth_key`, `two_factor_auth_enabled`, `create_date`, `update_date`, `login_date`, `discriminator_type`)
VALUES
    (1, 1, 0, NULL, 'テスト管理者', NULL, 'test-admin', '$2y$12$C3kYkAyWpOKV6WQ4WK/B9e5stageQpLe3RCPhKDd/YOCCnc.Z0N8C', NULL, 1, NULL, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00', NULL, 'member')
ON DUPLICATE KEY UPDATE
    `work_id` = VALUES(`work_id`),
    `authority_id` = VALUES(`authority_id`),
    `name` = VALUES(`name`),
    `login_id` = VALUES(`login_id`),
    `password` = VALUES(`password`),
    `sort_no` = VALUES(`sort_no`),
    `update_date` = VALUES(`update_date`),
    `discriminator_type` = VALUES(`discriminator_type`);

INSERT INTO `dtb_payment`
    (`id`, `creator_id`, `payment_method`, `charge`, `rule_min`, `rule_max`, `sort_no`, `fixed`, `visible`, `create_date`, `update_date`, `discriminator_type`)
VALUES
    (1, NULL, '代金引換', 0, 0, NULL, 1, 1, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00', 'payment'),
    (2, NULL, 'クレジットカード', 0, 0, NULL, 2, 1, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00', 'payment')
ON DUPLICATE KEY UPDATE
    `payment_method` = VALUES(`payment_method`),
    `charge` = VALUES(`charge`),
    `rule_min` = VALUES(`rule_min`),
    `rule_max` = VALUES(`rule_max`),
    `sort_no` = VALUES(`sort_no`),
    `fixed` = VALUES(`fixed`),
    `visible` = VALUES(`visible`),
    `update_date` = VALUES(`update_date`),
    `discriminator_type` = VALUES(`discriminator_type`);

-- Delivery method master + its time slots + per-prefecture base 送料.
-- Mirrors dtb_payment above: one visible default 宅配便 the checkout page
-- surfaces, three お届け時間 slots, and a flat 550 円 送料 for every
-- prefecture (dtb_delivery itself has NO fee column — the fee lives in
-- dtb_delivery_fee, keyed by (delivery_id, pref_id)).
INSERT INTO `dtb_delivery`
    (`id`, `creator_id`, `sale_type_id`, `name`, `service_name`, `description`, `confirm_url`, `sort_no`, `visible`, `create_date`, `update_date`, `discriminator_type`)
VALUES
    (1, NULL, 1, 'サンプル宅配便', 'サンプル宅配便', NULL, NULL, 1, 1, NOW(), NOW(), 'delivery')
ON DUPLICATE KEY UPDATE
    `sale_type_id` = VALUES(`sale_type_id`),
    `name` = VALUES(`name`),
    `service_name` = VALUES(`service_name`),
    `sort_no` = VALUES(`sort_no`),
    `visible` = VALUES(`visible`),
    `update_date` = VALUES(`update_date`),
    `discriminator_type` = VALUES(`discriminator_type`);

INSERT INTO `dtb_delivery_time`
    (`id`, `delivery_id`, `delivery_time`, `sort_no`, `visible`, `create_date`, `update_date`, `discriminator_type`)
VALUES
    (1, 1, '午前中', 1, 1, NOW(), NOW(), 'delivery_time'),
    (2, 1, '14:00-16:00', 2, 1, NOW(), NOW(), 'delivery_time'),
    (3, 1, '16:00-18:00', 3, 1, NOW(), NOW(), 'delivery_time')
ON DUPLICATE KEY UPDATE
    `delivery_id` = VALUES(`delivery_id`),
    `delivery_time` = VALUES(`delivery_time`),
    `sort_no` = VALUES(`sort_no`),
    `visible` = VALUES(`visible`),
    `update_date` = VALUES(`update_date`),
    `discriminator_type` = VALUES(`discriminator_type`);

-- Flat 550 送料 for every prefecture (pref_id 1..47). id = pref_id keeps the
-- seed idempotent under ON DUPLICATE KEY without an AUTO_INCREMENT race.
INSERT INTO `dtb_delivery_fee`
    (`id`, `delivery_id`, `pref_id`, `fee`, `discriminator_type`)
VALUES
    (1, 1, 1, 550, 'delivery_fee'), (2, 1, 2, 550, 'delivery_fee'), (3, 1, 3, 550, 'delivery_fee'),
    (4, 1, 4, 550, 'delivery_fee'), (5, 1, 5, 550, 'delivery_fee'), (6, 1, 6, 550, 'delivery_fee'),
    (7, 1, 7, 550, 'delivery_fee'), (8, 1, 8, 550, 'delivery_fee'), (9, 1, 9, 550, 'delivery_fee'),
    (10, 1, 10, 550, 'delivery_fee'), (11, 1, 11, 550, 'delivery_fee'), (12, 1, 12, 550, 'delivery_fee'),
    (13, 1, 13, 550, 'delivery_fee'), (14, 1, 14, 550, 'delivery_fee'), (15, 1, 15, 550, 'delivery_fee'),
    (16, 1, 16, 550, 'delivery_fee'), (17, 1, 17, 550, 'delivery_fee'), (18, 1, 18, 550, 'delivery_fee'),
    (19, 1, 19, 550, 'delivery_fee'), (20, 1, 20, 550, 'delivery_fee'), (21, 1, 21, 550, 'delivery_fee'),
    (22, 1, 22, 550, 'delivery_fee'), (23, 1, 23, 550, 'delivery_fee'), (24, 1, 24, 550, 'delivery_fee'),
    (25, 1, 25, 550, 'delivery_fee'), (26, 1, 26, 550, 'delivery_fee'), (27, 1, 27, 550, 'delivery_fee'),
    (28, 1, 28, 550, 'delivery_fee'), (29, 1, 29, 550, 'delivery_fee'), (30, 1, 30, 550, 'delivery_fee'),
    (31, 1, 31, 550, 'delivery_fee'), (32, 1, 32, 550, 'delivery_fee'), (33, 1, 33, 550, 'delivery_fee'),
    (34, 1, 34, 550, 'delivery_fee'), (35, 1, 35, 550, 'delivery_fee'), (36, 1, 36, 550, 'delivery_fee'),
    (37, 1, 37, 550, 'delivery_fee'), (38, 1, 38, 550, 'delivery_fee'), (39, 1, 39, 550, 'delivery_fee'),
    (40, 1, 40, 550, 'delivery_fee'), (41, 1, 41, 550, 'delivery_fee'), (42, 1, 42, 550, 'delivery_fee'),
    (43, 1, 43, 550, 'delivery_fee'), (44, 1, 44, 550, 'delivery_fee'), (45, 1, 45, 550, 'delivery_fee'),
    (46, 1, 46, 550, 'delivery_fee'), (47, 1, 47, 550, 'delivery_fee')
ON DUPLICATE KEY UPDATE
    `delivery_id` = VALUES(`delivery_id`),
    `pref_id` = VALUES(`pref_id`),
    `fee` = VALUES(`fee`),
    `discriminator_type` = VALUES(`discriminator_type`);

INSERT INTO `dtb_layout`
    (`id`, `device_type_id`, `layout_name`, `create_date`, `update_date`, `discriminator_type`)
VALUES
    (1, 10, 'Default PC layout', '2026-01-01 00:00:00', '2026-01-01 00:00:00', 'layout')
ON DUPLICATE KEY UPDATE
    `device_type_id` = VALUES(`device_type_id`),
    `layout_name` = VALUES(`layout_name`),
    `update_date` = VALUES(`update_date`),
    `discriminator_type` = VALUES(`discriminator_type`);

INSERT INTO `dtb_mail_template`
    (`id`, `creator_id`, `name`, `file_name`, `mail_subject`, `create_date`, `update_date`, `deletable`, `discriminator_type`)
VALUES
    (1, NULL, 'Order mail', 'Mail/order.twig', 'Thank you for your order', '2026-01-01 00:00:00', '2026-01-01 00:00:00', 1, 'mailtemplate')
ON DUPLICATE KEY UPDATE
    `creator_id` = VALUES(`creator_id`),
    `name` = VALUES(`name`),
    `file_name` = VALUES(`file_name`),
    `mail_subject` = VALUES(`mail_subject`),
    `update_date` = VALUES(`update_date`),
    `deletable` = VALUES(`deletable`),
    `discriminator_type` = VALUES(`discriminator_type`);
