-- EC-CUBE system master rows required after schema + mtb_* initialization.
--
-- These dtb_* rows are application configuration masters, not Web-created
-- business data such as products, customers, carts, orders, shippings, or
-- favorites. They provide the stock admin affordance targets that EC-CUBE
-- expects to exist in a freshly installed shop.

INSERT INTO `dtb_member`
    (`id`, `work_id`, `authority_id`, `creator_id`, `name`, `department`, `login_id`, `password`, `salt`, `sort_no`, `two_factor_auth_key`, `two_factor_auth_enabled`, `create_date`, `update_date`, `login_date`, `discriminator_type`)
VALUES
    (1, 1, 0, NULL, 'テスト管理者', NULL, 'test-admin', '$2y$12$9D41HA4FyUPdmxy8OCW72OHSLs.ul6E7YRGot9Pkpcvw3umDiOVgC', NULL, 1, NULL, 0, '2026-01-01 00:00:00', '2026-01-01 00:00:00', NULL, 'member')
ON DUPLICATE KEY UPDATE
    `work_id` = VALUES(`work_id`),
    `authority_id` = VALUES(`authority_id`),
    `name` = VALUES(`name`),
    `login_id` = VALUES(`login_id`),
    `password` = VALUES(`password`),
    `sort_no` = VALUES(`sort_no`),
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
