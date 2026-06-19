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
