-- =============================================================================
-- BeMart Schema — authored from first principles
-- =============================================================================
--
-- Primary source: sql/diff/entity-vs-eccube.md (field map, type, constraint,
-- and hazard documentation for all 34 BeMart Entity ↔ EC-CUBE table pairs).
-- Structural facts (column types, nullable flags, key shapes) were verified
-- against the EC-CUBE 4.3 raw dump (sql/schema/ec-cube-4.3-mysql-mysqldump.sql)
-- and are used here as functional facts, which are not subject to copyright.
--
-- Differentiations from the original EC-CUBE Doctrine-generated dump:
--   1. Index names: Doctrine hash identifiers (IDX_4A1F70B181EC865B, etc.)
--      replaced with idx_<table>_<col> descriptive names.
--   2. COMMENT '(DC2Type:...)' annotations stripped — BeMart does not use
--      Doctrine and those markers carry no meaning here.
--   3. MariaDB sandbox-mode preamble (M!999999\- enable the sandbox mode)
--      removed; MySQL 8.0 / MariaDB 10.11 compatible plain SQL.
--   4. Phase 2b migration checklist items pre-integrated:
--      - dtb_member: added email column + UNIQUE KEY on login_id
--      - dtb_customer: secret_key changed to NULL-allowed; added UNIQUE KEY
--        on reset_key; kept UNIQUE KEY on secret_key as partial uniqueness
--      - dtb_mail_template: added body and html_body columns
--      - dtb_delivery: added default_fee column for DeliveryEntity::feeBase
--      - dtb_customer_favorite_product: added UNIQUE KEY (customer_id, product_id)
--      - dtb_plugin: added UNIQUE KEY on code
--   5. BeMart-specific indexes from sql/migrations/001 and 002 merged in.
--
-- Copyright: BeMart project, authored 2025.
-- License:   see project root.
--
-- Load order: wrap with SET FOREIGN_KEY_CHECKS=0 / SET FOREIGN_KEY_CHECKS=1
-- (as setup-db.sh already does) — FK references span CREATE TABLE order.
-- =============================================================================

-- ---------------------------------------------------------------------------
-- mtb_* master / reference tables (no foreign keys — safe to create first)
-- ---------------------------------------------------------------------------

CREATE TABLE `mtb_authority` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_country` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_csv_type` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_customer_order_status` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_customer_status` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_device_type` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_job` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_login_history_status` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_order_item_type` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_order_status` (
  `id`                  smallint(5) unsigned NOT NULL,
  `display_order_count` tinyint(1) NOT NULL DEFAULT 0,
  `name`                varchar(255) NOT NULL,
  `sort_no`             smallint(5) unsigned NOT NULL,
  `discriminator_type`  varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_order_status_color` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_page_max` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_pref` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_product_list_max` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_product_list_order_by` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_product_status` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_rounding_type` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_sale_type` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_sex` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_tax_display_type` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_tax_type` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `mtb_work` (
  `id`                smallint(5) unsigned NOT NULL,
  `name`              varchar(255) NOT NULL,
  `sort_no`           smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_member (AdminEntity)
-- Phase 2b additions: email column, UNIQUE KEY on login_id
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_member` (
  `id`                      int(10) unsigned NOT NULL AUTO_INCREMENT,
  `work_id`                 smallint(5) unsigned DEFAULT NULL,
  `authority_id`            smallint(5) unsigned DEFAULT NULL,
  `creator_id`              int(10) unsigned DEFAULT NULL,
  `name`                    varchar(255) DEFAULT NULL,
  `department`              varchar(255) DEFAULT NULL,
  `login_id`                varchar(255) NOT NULL,
  `password`                varchar(255) NOT NULL,
  `salt`                    varchar(255) DEFAULT NULL,
  `sort_no`                 smallint(5) unsigned NOT NULL,
  `two_factor_auth_key`     varchar(255) DEFAULT NULL,
  `two_factor_auth_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `email`                   varchar(255) DEFAULT NULL,
  `create_date`             datetime NOT NULL,
  `update_date`             datetime NOT NULL,
  `login_date`              datetime DEFAULT NULL,
  `discriminator_type`      varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_member_login_id` (`login_id`),
  KEY `idx_member_work` (`work_id`),
  KEY `idx_member_authority` (`authority_id`),
  KEY `idx_member_creator` (`creator_id`),
  CONSTRAINT `fk_member_work`      FOREIGN KEY (`work_id`)      REFERENCES `mtb_work` (`id`),
  CONSTRAINT `fk_member_authority` FOREIGN KEY (`authority_id`) REFERENCES `mtb_authority` (`id`),
  CONSTRAINT `fk_member_creator`   FOREIGN KEY (`creator_id`)   REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_base_info (BaseInfoEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_base_info` (
  `id`                                  int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id`                          smallint(5) unsigned DEFAULT NULL,
  `pref_id`                             smallint(5) unsigned DEFAULT NULL,
  `company_name`                        varchar(255) DEFAULT NULL,
  `company_kana`                        varchar(255) DEFAULT NULL,
  `postal_code`                         varchar(8) DEFAULT NULL,
  `addr01`                              varchar(255) DEFAULT NULL,
  `addr02`                              varchar(255) DEFAULT NULL,
  `phone_number`                        varchar(14) DEFAULT NULL,
  `business_hour`                       varchar(255) DEFAULT NULL,
  `email01`                             varchar(255) DEFAULT NULL,
  `email02`                             varchar(255) DEFAULT NULL,
  `email03`                             varchar(255) DEFAULT NULL,
  `email04`                             varchar(255) DEFAULT NULL,
  `shop_name`                           varchar(255) DEFAULT NULL,
  `shop_kana`                           varchar(255) DEFAULT NULL,
  `shop_name_eng`                       varchar(255) DEFAULT NULL,
  `update_date`                         datetime NOT NULL,
  `good_traded`                         varchar(4000) DEFAULT NULL,
  `message`                             varchar(4000) DEFAULT NULL,
  `delivery_free_amount`                decimal(12,2) unsigned DEFAULT NULL,
  `delivery_free_quantity`              int(10) unsigned DEFAULT NULL,
  `option_mypage_order_status_display`  tinyint(1) NOT NULL DEFAULT 1,
  `option_nostock_hidden`               tinyint(1) NOT NULL DEFAULT 0,
  `option_favorite_product`             tinyint(1) NOT NULL DEFAULT 1,
  `option_product_delivery_fee`         tinyint(1) NOT NULL DEFAULT 0,
  `invoice_registration_number`         varchar(255) DEFAULT NULL,
  `option_product_tax_rule`             tinyint(1) NOT NULL DEFAULT 0,
  `option_customer_activate`            tinyint(1) NOT NULL DEFAULT 1,
  `option_remember_me`                  tinyint(1) NOT NULL DEFAULT 1,
  `option_mail_notifier`                tinyint(1) NOT NULL DEFAULT 0,
  `authentication_key`                  varchar(255) DEFAULT NULL,
  `php_path`                            varchar(255) DEFAULT NULL,
  `option_point`                        tinyint(1) NOT NULL DEFAULT 1,
  `basic_point_rate`                    decimal(10,0) unsigned DEFAULT 1,
  `point_conversion_rate`               decimal(10,0) unsigned DEFAULT 1,
  `ga_id`                               varchar(255) DEFAULT NULL,
  `discriminator_type`                  varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_base_info_country` (`country_id`),
  KEY `idx_base_info_pref`    (`pref_id`),
  CONSTRAINT `fk_base_info_country` FOREIGN KEY (`country_id`) REFERENCES `mtb_country` (`id`),
  CONSTRAINT `fk_base_info_pref`    FOREIGN KEY (`pref_id`)    REFERENCES `mtb_pref` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_authority_role
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_authority_role` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `authority_id`       smallint(5) unsigned DEFAULT NULL,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `deny_url`           varchar(4000) NOT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_authority_role_authority` (`authority_id`),
  KEY `idx_authority_role_creator`   (`creator_id`),
  CONSTRAINT `fk_authority_role_authority` FOREIGN KEY (`authority_id`) REFERENCES `mtb_authority` (`id`),
  CONSTRAINT `fk_authority_role_creator`   FOREIGN KEY (`creator_id`)   REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_layout (LayoutEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_layout` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `device_type_id`     smallint(5) unsigned DEFAULT NULL,
  `layout_name`        varchar(255) DEFAULT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_layout_device_type` (`device_type_id`),
  CONSTRAINT `fk_layout_device_type` FOREIGN KEY (`device_type_id`) REFERENCES `mtb_device_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_block (BlockEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_block` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `device_type_id`     smallint(5) unsigned DEFAULT NULL,
  `block_name`         varchar(255) DEFAULT NULL,
  `file_name`          varchar(255) NOT NULL,
  `use_controller`     tinyint(1) NOT NULL DEFAULT 0,
  `deletable`          tinyint(1) NOT NULL DEFAULT 1,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_block_device_file` (`device_type_id`, `file_name`),
  KEY `idx_block_device_type` (`device_type_id`),
  CONSTRAINT `fk_block_device_type` FOREIGN KEY (`device_type_id`) REFERENCES `mtb_device_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_block_position
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_block_position` (
  `section`            int(10) unsigned NOT NULL,
  `block_id`           int(10) unsigned NOT NULL,
  `layout_id`          int(10) unsigned NOT NULL,
  `block_row`          int(10) unsigned DEFAULT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`section`, `block_id`, `layout_id`),
  KEY `idx_block_position_block`  (`block_id`),
  KEY `idx_block_position_layout` (`layout_id`),
  CONSTRAINT `fk_block_position_block`  FOREIGN KEY (`block_id`)  REFERENCES `dtb_block` (`id`),
  CONSTRAINT `fk_block_position_layout` FOREIGN KEY (`layout_id`) REFERENCES `dtb_layout` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_page (PageEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_page` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `master_page_id`     int(10) unsigned DEFAULT NULL,
  `page_name`          varchar(255) DEFAULT NULL,
  `url`                varchar(255) NOT NULL,
  `file_name`          varchar(255) DEFAULT NULL,
  `edit_type`          smallint(5) unsigned NOT NULL DEFAULT 1,
  `author`             varchar(255) DEFAULT NULL,
  `description`        varchar(255) DEFAULT NULL,
  `keyword`            varchar(255) DEFAULT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `meta_robots`        varchar(255) DEFAULT NULL,
  `meta_tags`          varchar(4000) DEFAULT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_page_url`         (`url`),
  KEY `idx_page_master_page` (`master_page_id`),
  CONSTRAINT `fk_page_master_page` FOREIGN KEY (`master_page_id`) REFERENCES `dtb_page` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_page_layout
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_page_layout` (
  `page_id`            int(10) unsigned NOT NULL,
  `layout_id`          int(10) unsigned NOT NULL,
  `sort_no`            smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`page_id`, `layout_id`),
  KEY `idx_page_layout_page`   (`page_id`),
  KEY `idx_page_layout_layout` (`layout_id`),
  CONSTRAINT `fk_page_layout_page`   FOREIGN KEY (`page_id`)   REFERENCES `dtb_page` (`id`),
  CONSTRAINT `fk_page_layout_layout` FOREIGN KEY (`layout_id`) REFERENCES `dtb_layout` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_template (TemplateEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_template` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `device_type_id`     smallint(5) unsigned DEFAULT NULL,
  `template_code`      varchar(255) NOT NULL,
  `template_name`      varchar(255) NOT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_template_device_type` (`device_type_id`),
  CONSTRAINT `fk_template_device_type` FOREIGN KEY (`device_type_id`) REFERENCES `mtb_device_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_calendar (CalendarHolidayEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_calendar` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title`              varchar(255) DEFAULT NULL,
  `holiday`            datetime NOT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_payment (PaymentMethodAdminEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_payment` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `payment_method`     varchar(255) DEFAULT NULL,
  `charge`             decimal(12,2) unsigned DEFAULT 0.00,
  `rule_max`           decimal(12,2) unsigned DEFAULT NULL,
  `sort_no`            smallint(5) unsigned DEFAULT NULL,
  `fixed`              tinyint(1) NOT NULL DEFAULT 1,
  `payment_image`      varchar(255) DEFAULT NULL,
  `rule_min`           decimal(12,2) unsigned DEFAULT NULL,
  `method_class`       varchar(255) DEFAULT NULL,
  `visible`            tinyint(1) NOT NULL DEFAULT 1,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payment_creator` (`creator_id`),
  CONSTRAINT `fk_payment_creator` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_delivery (DeliveryEntity)
-- Phase 2b addition: default_fee column for DeliveryEntity::feeBase
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_delivery` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `sale_type_id`       smallint(5) unsigned DEFAULT NULL,
  `name`               varchar(255) DEFAULT NULL,
  `service_name`       varchar(255) DEFAULT NULL,
  `description`        varchar(4000) DEFAULT NULL,
  `confirm_url`        varchar(4000) DEFAULT NULL,
  `default_fee`        decimal(12,2) DEFAULT NULL,
  `sort_no`            int(10) unsigned DEFAULT NULL,
  `visible`            tinyint(1) NOT NULL DEFAULT 1,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_delivery_creator`   (`creator_id`),
  KEY `idx_delivery_sale_type` (`sale_type_id`),
  CONSTRAINT `fk_delivery_creator`   FOREIGN KEY (`creator_id`)   REFERENCES `dtb_member` (`id`),
  CONSTRAINT `fk_delivery_sale_type` FOREIGN KEY (`sale_type_id`) REFERENCES `mtb_sale_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_delivery_duration (referenced by dtb_product_class)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_delivery_duration` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name`               varchar(255) DEFAULT NULL,
  `duration`           smallint(6) NOT NULL DEFAULT 0,
  `sort_no`            int(10) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_delivery_fee (per-pref fees; referenced by DeliveryEntity join)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_delivery_fee` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_id`        int(10) unsigned DEFAULT NULL,
  `pref_id`            smallint(5) unsigned DEFAULT NULL,
  `fee`                decimal(12,2) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_delivery_fee_delivery` (`delivery_id`),
  KEY `idx_delivery_fee_pref`     (`pref_id`),
  CONSTRAINT `fk_delivery_fee_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `dtb_delivery` (`id`),
  CONSTRAINT `fk_delivery_fee_pref`     FOREIGN KEY (`pref_id`)     REFERENCES `mtb_pref` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_delivery_time (delivery time-slots per delivery method)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_delivery_time` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_id`        int(10) unsigned DEFAULT NULL,
  `delivery_time`      varchar(255) NOT NULL,
  `sort_no`            smallint(5) unsigned NOT NULL,
  `visible`            tinyint(1) NOT NULL DEFAULT 1,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_delivery_time_delivery` (`delivery_id`),
  CONSTRAINT `fk_delivery_time_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `dtb_delivery` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_payment_option (delivery ↔ payment linkage)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_payment_option` (
  `delivery_id`        int(10) unsigned NOT NULL,
  `payment_id`         int(10) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`delivery_id`, `payment_id`),
  KEY `idx_payment_option_delivery` (`delivery_id`),
  KEY `idx_payment_option_payment`  (`payment_id`),
  CONSTRAINT `fk_payment_option_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `dtb_delivery` (`id`),
  CONSTRAINT `fk_payment_option_payment`  FOREIGN KEY (`payment_id`)  REFERENCES `dtb_payment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_customer (CustomerEntity + PasswordResetTokenEntity columns)
-- Phase 2b: secret_key changed to NULL-allowed; UNIQUE KEY on reset_key
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_customer` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_status_id` smallint(5) unsigned DEFAULT NULL,
  `sex_id`             smallint(5) unsigned DEFAULT NULL,
  `job_id`             smallint(5) unsigned DEFAULT NULL,
  `country_id`         smallint(5) unsigned DEFAULT NULL,
  `pref_id`            smallint(5) unsigned DEFAULT NULL,
  `name01`             varchar(255) NOT NULL,
  `name02`             varchar(255) NOT NULL,
  `kana01`             varchar(255) DEFAULT NULL,
  `kana02`             varchar(255) DEFAULT NULL,
  `company_name`       varchar(255) DEFAULT NULL,
  `postal_code`        varchar(8) DEFAULT NULL,
  `addr01`             varchar(255) DEFAULT NULL,
  `addr02`             varchar(255) DEFAULT NULL,
  `email`              varchar(255) NOT NULL,
  `phone_number`       varchar(14) DEFAULT NULL,
  `birth`              datetime DEFAULT NULL,
  `password`           varchar(255) NOT NULL,
  `salt`               varchar(255) DEFAULT NULL,
  `secret_key`         varchar(255) DEFAULT NULL,
  `first_buy_date`     datetime DEFAULT NULL,
  `last_buy_date`      datetime DEFAULT NULL,
  `buy_times`          decimal(10,0) unsigned DEFAULT 0,
  `buy_total`          decimal(12,2) unsigned DEFAULT 0.00,
  `note`               varchar(4000) DEFAULT NULL,
  `reset_key`          varchar(255) DEFAULT NULL,
  `reset_expire`       datetime DEFAULT NULL,
  `point`              decimal(12,0) NOT NULL DEFAULT 0,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customer_secret_key` (`secret_key`),
  UNIQUE KEY `uq_customer_reset_key`  (`reset_key`),
  KEY `idx_customer_status`       (`customer_status_id`),
  KEY `idx_customer_sex`          (`sex_id`),
  KEY `idx_customer_job`          (`job_id`),
  KEY `idx_customer_country`      (`country_id`),
  KEY `idx_customer_pref`         (`pref_id`),
  KEY `idx_customer_buy_times`    (`buy_times`),
  KEY `idx_customer_buy_total`    (`buy_total`),
  KEY `idx_customer_create_date`  (`create_date`),
  KEY `idx_customer_update_date`  (`update_date`),
  KEY `idx_customer_last_buy_date` (`last_buy_date`),
  KEY `idx_customer_email`        (`email`),
  -- merged from sql/migrations/002
  KEY `idx_customer_reset_key_lookup` (`reset_key`, `id`, `reset_expire`),
  CONSTRAINT `fk_customer_status`  FOREIGN KEY (`customer_status_id`) REFERENCES `mtb_customer_status` (`id`),
  CONSTRAINT `fk_customer_sex`     FOREIGN KEY (`sex_id`)             REFERENCES `mtb_sex` (`id`),
  CONSTRAINT `fk_customer_job`     FOREIGN KEY (`job_id`)             REFERENCES `mtb_job` (`id`),
  CONSTRAINT `fk_customer_pref`    FOREIGN KEY (`pref_id`)            REFERENCES `mtb_pref` (`id`),
  CONSTRAINT `fk_customer_country` FOREIGN KEY (`country_id`)         REFERENCES `mtb_country` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_customer_address (AddressEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_customer_address` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id`        int(10) unsigned DEFAULT NULL,
  `country_id`         smallint(5) unsigned DEFAULT NULL,
  `pref_id`            smallint(5) unsigned DEFAULT NULL,
  `name01`             varchar(255) NOT NULL,
  `name02`             varchar(255) NOT NULL,
  `kana01`             varchar(255) DEFAULT NULL,
  `kana02`             varchar(255) DEFAULT NULL,
  `company_name`       varchar(255) DEFAULT NULL,
  `postal_code`        varchar(8) DEFAULT NULL,
  `addr01`             varchar(255) DEFAULT NULL,
  `addr02`             varchar(255) DEFAULT NULL,
  `phone_number`       varchar(14) DEFAULT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer_address_customer` (`customer_id`),
  KEY `idx_customer_address_country`  (`country_id`),
  KEY `idx_customer_address_pref`     (`pref_id`),
  CONSTRAINT `fk_customer_address_customer` FOREIGN KEY (`customer_id`) REFERENCES `dtb_customer` (`id`),
  CONSTRAINT `fk_customer_address_pref`     FOREIGN KEY (`pref_id`)     REFERENCES `mtb_pref` (`id`),
  CONSTRAINT `fk_customer_address_country`  FOREIGN KEY (`country_id`)  REFERENCES `mtb_country` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_login_history (LoginHistoryEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_login_history` (
  `id`                      int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login_history_status_id` smallint(5) unsigned NOT NULL,
  `member_id`               int(10) unsigned DEFAULT NULL,
  `user_name`               longtext DEFAULT NULL,
  `client_ip`               longtext DEFAULT NULL,
  `create_date`             datetime NOT NULL,
  `update_date`             datetime NOT NULL,
  `discriminator_type`      varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_history_status` (`login_history_status_id`),
  KEY `idx_login_history_member` (`member_id`),
  CONSTRAINT `fk_login_history_status` FOREIGN KEY (`login_history_status_id`) REFERENCES `mtb_login_history_status` (`id`),
  CONSTRAINT `fk_login_history_member` FOREIGN KEY (`member_id`)               REFERENCES `dtb_member` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_mail_template (MailTemplateEntity)
-- Phase 2b additions: body and html_body columns
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_mail_template` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `name`               varchar(255) DEFAULT NULL,
  `file_name`          varchar(255) DEFAULT NULL,
  `mail_subject`       varchar(255) DEFAULT NULL,
  `body`               longtext DEFAULT NULL,
  `html_body`          longtext DEFAULT NULL,
  `deletable`          tinyint(1) NOT NULL DEFAULT 0,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mail_template_creator` (`creator_id`),
  CONSTRAINT `fk_mail_template_creator` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_class_name (ClassNameEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_class_name` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `backend_name`       varchar(255) DEFAULT NULL,
  `name`               varchar(255) NOT NULL,
  `sort_no`            int(10) unsigned NOT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_class_name_creator` (`creator_id`),
  CONSTRAINT `fk_class_name_creator` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_class_category (ClassCategoryEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_class_category` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `class_name_id`      int(10) unsigned DEFAULT NULL,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `backend_name`       varchar(255) DEFAULT NULL,
  `name`               varchar(255) NOT NULL,
  `sort_no`            int(10) unsigned NOT NULL,
  `visible`            tinyint(1) NOT NULL DEFAULT 1,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_class_category_class_name` (`class_name_id`),
  KEY `idx_class_category_creator`    (`creator_id`),
  CONSTRAINT `fk_class_category_class_name` FOREIGN KEY (`class_name_id`) REFERENCES `dtb_class_name` (`id`),
  CONSTRAINT `fk_class_category_creator`    FOREIGN KEY (`creator_id`)    REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_tag (TagEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_tag` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name`               varchar(255) NOT NULL,
  `sort_no`            smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_category (CategoryEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_category` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_category_id` int(10) unsigned DEFAULT NULL,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `category_name`      varchar(255) NOT NULL,
  `hierarchy`          int(10) unsigned NOT NULL,
  `sort_no`            int(11) NOT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category_parent`  (`parent_category_id`),
  KEY `idx_category_creator` (`creator_id`),
  CONSTRAINT `fk_category_parent`  FOREIGN KEY (`parent_category_id`) REFERENCES `dtb_category` (`id`),
  CONSTRAINT `fk_category_creator` FOREIGN KEY (`creator_id`)         REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_product (ProductEntity — header row)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_product` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `product_status_id`  smallint(5) unsigned DEFAULT NULL,
  `name`               varchar(255) NOT NULL,
  `note`               longtext DEFAULT NULL,
  `description_list`   longtext DEFAULT NULL,
  `description_detail` longtext DEFAULT NULL,
  `search_word`        longtext DEFAULT NULL,
  `free_area`          longtext DEFAULT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_creator`       (`creator_id`),
  KEY `idx_product_status`        (`product_status_id`),
  CONSTRAINT `fk_product_creator` FOREIGN KEY (`creator_id`)        REFERENCES `dtb_member` (`id`),
  CONSTRAINT `fk_product_status`  FOREIGN KEY (`product_status_id`) REFERENCES `mtb_product_status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_product_class (ProductClassEntity / ProductEntity price+stock)
-- Includes BeMart indexes merged from sql/migrations/001
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_product_class` (
  `id`                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id`           int(10) unsigned DEFAULT NULL,
  `sale_type_id`         smallint(5) unsigned DEFAULT NULL,
  `class_category_id1`   int(10) unsigned DEFAULT NULL,
  `class_category_id2`   int(10) unsigned DEFAULT NULL,
  `delivery_duration_id` int(10) unsigned DEFAULT NULL,
  `creator_id`           int(10) unsigned DEFAULT NULL,
  `product_code`         varchar(255) DEFAULT NULL,
  `stock`                decimal(10,0) DEFAULT NULL,
  `stock_unlimited`      tinyint(1) NOT NULL DEFAULT 0,
  `sale_limit`           decimal(10,0) unsigned DEFAULT NULL,
  `price01`              decimal(12,2) DEFAULT NULL,
  `price02`              decimal(12,2) NOT NULL,
  `delivery_fee`         decimal(12,2) unsigned DEFAULT NULL,
  `visible`              tinyint(1) NOT NULL DEFAULT 1,
  `create_date`          datetime NOT NULL,
  `update_date`          datetime NOT NULL,
  `currency_code`        varchar(255) DEFAULT NULL,
  `point_rate`           decimal(10,0) unsigned DEFAULT NULL,
  `discriminator_type`   varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_class_product`           (`product_id`),
  KEY `idx_product_class_sale_type`         (`sale_type_id`),
  KEY `idx_product_class_category1`         (`class_category_id1`),
  KEY `idx_product_class_category2`         (`class_category_id2`),
  KEY `idx_product_class_delivery_duration` (`delivery_duration_id`),
  KEY `idx_product_class_creator`           (`creator_id`),
  KEY `idx_product_class_price02`           (`price02`),
  KEY `idx_product_class_stock_unlimited`   (`stock`, `stock_unlimited`),
  -- merged from sql/migrations/001
  KEY `idx_bemart_pc_code_default`   (`product_code`, `class_category_id1`, `class_category_id2`, `id`, `product_id`),
  KEY `idx_bemart_pc_default_order`  (`class_category_id1`, `class_category_id2`, `id`, `product_id`),
  CONSTRAINT `fk_product_class_product`           FOREIGN KEY (`product_id`)           REFERENCES `dtb_product` (`id`),
  CONSTRAINT `fk_product_class_sale_type`         FOREIGN KEY (`sale_type_id`)         REFERENCES `mtb_sale_type` (`id`),
  CONSTRAINT `fk_product_class_category1`         FOREIGN KEY (`class_category_id1`)   REFERENCES `dtb_class_category` (`id`),
  CONSTRAINT `fk_product_class_category2`         FOREIGN KEY (`class_category_id2`)   REFERENCES `dtb_class_category` (`id`),
  CONSTRAINT `fk_product_class_delivery_duration` FOREIGN KEY (`delivery_duration_id`) REFERENCES `dtb_delivery_duration` (`id`),
  CONSTRAINT `fk_product_class_creator`           FOREIGN KEY (`creator_id`)           REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_product_image (referenced by sql queries and migration 001)
-- Includes BeMart index merged from sql/migrations/001
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_product_image` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id`         int(10) unsigned DEFAULT NULL,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `file_name`          varchar(255) NOT NULL,
  `sort_no`            smallint(5) unsigned NOT NULL,
  `create_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  -- merged from sql/migrations/001
  KEY `idx_bemart_pi_product_sort` (`product_id`, `sort_no`, `id`),
  KEY `idx_product_image_creator`  (`creator_id`),
  CONSTRAINT `fk_product_image_product` FOREIGN KEY (`product_id`) REFERENCES `dtb_product` (`id`),
  CONSTRAINT `fk_product_image_creator` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_product_category (product ↔ category linkage)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_product_category` (
  `product_id`         int(10) unsigned NOT NULL,
  `category_id`        int(10) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`product_id`, `category_id`),
  KEY `idx_product_category_product`  (`product_id`),
  KEY `idx_product_category_category` (`category_id`),
  CONSTRAINT `fk_product_category_product`  FOREIGN KEY (`product_id`)  REFERENCES `dtb_product` (`id`),
  CONSTRAINT `fk_product_category_category` FOREIGN KEY (`category_id`) REFERENCES `dtb_category` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_product_stock (inventory history)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_product_stock` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_class_id`   int(10) unsigned DEFAULT NULL,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `stock`              decimal(10,0) DEFAULT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_stock_class`   (`product_class_id`),
  KEY `idx_product_stock_creator` (`creator_id`),
  CONSTRAINT `fk_product_stock_class`   FOREIGN KEY (`product_class_id`) REFERENCES `dtb_product_class` (`id`),
  CONSTRAINT `fk_product_stock_creator` FOREIGN KEY (`creator_id`)       REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_product_tag
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_product_tag` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id`         int(10) unsigned DEFAULT NULL,
  `tag_id`             int(10) unsigned DEFAULT NULL,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `create_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_product_tag_product` (`product_id`),
  KEY `idx_product_tag_tag`     (`tag_id`),
  KEY `idx_product_tag_creator` (`creator_id`),
  CONSTRAINT `fk_product_tag_product` FOREIGN KEY (`product_id`) REFERENCES `dtb_product` (`id`),
  CONSTRAINT `fk_product_tag_tag`     FOREIGN KEY (`tag_id`)     REFERENCES `dtb_tag` (`id`),
  CONSTRAINT `fk_product_tag_creator` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_customer_favorite_product (FavoriteEntity)
-- Phase 2b addition: UNIQUE KEY (customer_id, product_id)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_customer_favorite_product` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id`        int(10) unsigned DEFAULT NULL,
  `product_id`         int(10) unsigned DEFAULT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_favorite_customer_product` (`customer_id`, `product_id`),
  KEY `idx_favorite_customer` (`customer_id`),
  KEY `idx_favorite_product`  (`product_id`),
  CONSTRAINT `fk_favorite_customer` FOREIGN KEY (`customer_id`) REFERENCES `dtb_customer` (`id`),
  CONSTRAINT `fk_favorite_product`  FOREIGN KEY (`product_id`)  REFERENCES `dtb_product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_cart (CartEntity header)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_cart` (
  `id`                  int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id`         int(10) unsigned DEFAULT NULL,
  `cart_key`            varchar(255) DEFAULT NULL,
  `pre_order_id`        varchar(255) DEFAULT NULL,
  `total_price`         decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `delivery_fee_total`  decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `sort_no`             smallint(5) unsigned DEFAULT NULL,
  `add_point`           decimal(12,0) unsigned NOT NULL DEFAULT 0,
  `use_point`           decimal(12,0) unsigned NOT NULL DEFAULT 0,
  `create_date`         datetime NOT NULL,
  `update_date`         datetime NOT NULL,
  `discriminator_type`  varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_pre_order_id` (`pre_order_id`),
  KEY `idx_cart_customer`    (`customer_id`),
  KEY `idx_cart_update_date` (`update_date`),
  CONSTRAINT `fk_cart_customer` FOREIGN KEY (`customer_id`) REFERENCES `dtb_customer` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_cart_item (CartItemEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_cart_item` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_class_id`   int(10) unsigned DEFAULT NULL,
  `cart_id`            int(10) unsigned DEFAULT NULL,
  `price`              decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity`           decimal(10,0) NOT NULL DEFAULT 0,
  `point_rate`         decimal(10,0) unsigned DEFAULT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cart_item_product_class` (`product_class_id`),
  KEY `idx_cart_item_cart`          (`cart_id`),
  CONSTRAINT `fk_cart_item_product_class` FOREIGN KEY (`product_class_id`) REFERENCES `dtb_product_class` (`id`),
  CONSTRAINT `fk_cart_item_cart`          FOREIGN KEY (`cart_id`)          REFERENCES `dtb_cart` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_order (OrderEntity pre-order + FinalizedOrderEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_order` (
  `id`                    int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id`           int(10) unsigned DEFAULT NULL,
  `country_id`            smallint(5) unsigned DEFAULT NULL,
  `pref_id`               smallint(5) unsigned DEFAULT NULL,
  `sex_id`                smallint(5) unsigned DEFAULT NULL,
  `job_id`                smallint(5) unsigned DEFAULT NULL,
  `payment_id`            int(10) unsigned DEFAULT NULL,
  `device_type_id`        smallint(5) unsigned DEFAULT NULL,
  `order_status_id`       smallint(5) unsigned DEFAULT NULL,
  `pre_order_id`          varchar(255) DEFAULT NULL,
  `order_no`              varchar(255) DEFAULT NULL,
  `message`               varchar(4000) DEFAULT NULL,
  `name01`                varchar(255) NOT NULL,
  `name02`                varchar(255) NOT NULL,
  `kana01`                varchar(255) DEFAULT NULL,
  `kana02`                varchar(255) DEFAULT NULL,
  `company_name`          varchar(255) DEFAULT NULL,
  `email`                 varchar(255) DEFAULT NULL,
  `phone_number`          varchar(14) DEFAULT NULL,
  `postal_code`           varchar(8) DEFAULT NULL,
  `addr01`                varchar(255) DEFAULT NULL,
  `addr02`                varchar(255) DEFAULT NULL,
  `birth`                 datetime DEFAULT NULL,
  `subtotal`              decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `discount`              decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `delivery_fee_total`    decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `charge`                decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `tax`                   decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `total`                 decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `payment_total`         decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `payment_method`        varchar(255) DEFAULT NULL,
  `note`                  varchar(4000) DEFAULT NULL,
  `add_point`             decimal(12,0) unsigned NOT NULL DEFAULT 0,
  `use_point`             decimal(12,0) unsigned NOT NULL DEFAULT 0,
  `order_date`            datetime DEFAULT NULL,
  `payment_date`          datetime DEFAULT NULL,
  `currency_code`         varchar(255) DEFAULT NULL,
  `complete_message`      longtext DEFAULT NULL,
  `complete_mail_message` longtext DEFAULT NULL,
  `create_date`           datetime NOT NULL,
  `update_date`           datetime NOT NULL,
  `discriminator_type`    varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_pre_order_id` (`pre_order_id`),
  KEY `idx_order_customer`     (`customer_id`),
  KEY `idx_order_country`      (`country_id`),
  KEY `idx_order_pref`         (`pref_id`),
  KEY `idx_order_sex`          (`sex_id`),
  KEY `idx_order_job`          (`job_id`),
  KEY `idx_order_payment`      (`payment_id`),
  KEY `idx_order_device_type`  (`device_type_id`),
  KEY `idx_order_status`       (`order_status_id`),
  KEY `idx_order_email`        (`email`),
  KEY `idx_order_order_date`   (`order_date`),
  KEY `idx_order_payment_date` (`payment_date`),
  KEY `idx_order_update_date`  (`update_date`),
  KEY `idx_order_order_no`     (`order_no`),
  CONSTRAINT `fk_order_customer`    FOREIGN KEY (`customer_id`)    REFERENCES `dtb_customer` (`id`),
  CONSTRAINT `fk_order_pref`        FOREIGN KEY (`pref_id`)        REFERENCES `mtb_pref` (`id`),
  CONSTRAINT `fk_order_country`     FOREIGN KEY (`country_id`)     REFERENCES `mtb_country` (`id`),
  CONSTRAINT `fk_order_sex`         FOREIGN KEY (`sex_id`)         REFERENCES `mtb_sex` (`id`),
  CONSTRAINT `fk_order_job`         FOREIGN KEY (`job_id`)         REFERENCES `mtb_job` (`id`),
  CONSTRAINT `fk_order_payment`     FOREIGN KEY (`payment_id`)     REFERENCES `dtb_payment` (`id`),
  CONSTRAINT `fk_order_device_type` FOREIGN KEY (`device_type_id`) REFERENCES `mtb_device_type` (`id`),
  CONSTRAINT `fk_order_status`      FOREIGN KEY (`order_status_id`) REFERENCES `mtb_order_status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_shipping (ShippingAddressEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_shipping` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id`           int(10) unsigned DEFAULT NULL,
  `country_id`         smallint(5) unsigned DEFAULT NULL,
  `pref_id`            smallint(5) unsigned DEFAULT NULL,
  `delivery_id`        int(10) unsigned DEFAULT NULL,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `name01`             varchar(255) NOT NULL,
  `name02`             varchar(255) NOT NULL,
  `kana01`             varchar(255) DEFAULT NULL,
  `kana02`             varchar(255) DEFAULT NULL,
  `company_name`       varchar(255) DEFAULT NULL,
  `phone_number`       varchar(14) DEFAULT NULL,
  `postal_code`        varchar(8) DEFAULT NULL,
  `addr01`             varchar(255) DEFAULT NULL,
  `addr02`             varchar(255) DEFAULT NULL,
  `delivery_name`      varchar(255) DEFAULT NULL,
  `time_id`            int(10) unsigned DEFAULT NULL,
  `delivery_time`      varchar(255) DEFAULT NULL,
  `delivery_date`      datetime DEFAULT NULL,
  `shipping_date`      datetime DEFAULT NULL,
  `tracking_number`    varchar(255) DEFAULT NULL,
  `note`               varchar(4000) DEFAULT NULL,
  `sort_no`            smallint(5) unsigned DEFAULT NULL,
  `mail_send_date`     datetime DEFAULT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_shipping_order`    (`order_id`),
  KEY `idx_shipping_country`  (`country_id`),
  KEY `idx_shipping_pref`     (`pref_id`),
  KEY `idx_shipping_delivery` (`delivery_id`),
  KEY `idx_shipping_creator`  (`creator_id`),
  CONSTRAINT `fk_shipping_order`    FOREIGN KEY (`order_id`)    REFERENCES `dtb_order` (`id`),
  CONSTRAINT `fk_shipping_pref`     FOREIGN KEY (`pref_id`)     REFERENCES `mtb_pref` (`id`),
  CONSTRAINT `fk_shipping_country`  FOREIGN KEY (`country_id`)  REFERENCES `mtb_country` (`id`),
  CONSTRAINT `fk_shipping_delivery` FOREIGN KEY (`delivery_id`) REFERENCES `dtb_delivery` (`id`),
  CONSTRAINT `fk_shipping_creator`  FOREIGN KEY (`creator_id`)  REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_order_item (OrderItemEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_order_item` (
  `id`                    int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id`              int(10) unsigned DEFAULT NULL,
  `product_id`            int(10) unsigned DEFAULT NULL,
  `product_class_id`      int(10) unsigned DEFAULT NULL,
  `shipping_id`           int(10) unsigned DEFAULT NULL,
  `rounding_type_id`      smallint(5) unsigned DEFAULT NULL,
  `tax_type_id`           smallint(5) unsigned DEFAULT NULL,
  `tax_display_type_id`   smallint(5) unsigned DEFAULT NULL,
  `order_item_type_id`    smallint(5) unsigned DEFAULT NULL,
  `product_name`          varchar(255) NOT NULL,
  `product_code`          varchar(255) DEFAULT NULL,
  `class_name1`           varchar(255) DEFAULT NULL,
  `class_name2`           varchar(255) DEFAULT NULL,
  `class_category_name1`  varchar(255) DEFAULT NULL,
  `class_category_name2`  varchar(255) DEFAULT NULL,
  `price`                 decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity`              decimal(10,0) NOT NULL DEFAULT 0,
  `tax`                   decimal(10,0) NOT NULL DEFAULT 0,
  `tax_rate`              decimal(10,0) unsigned NOT NULL DEFAULT 0,
  `tax_adjust`            decimal(10,0) unsigned NOT NULL DEFAULT 0,
  `tax_rule_id`           smallint(5) unsigned DEFAULT NULL,
  `currency_code`         varchar(255) DEFAULT NULL,
  `processor_name`        varchar(255) DEFAULT NULL,
  `point_rate`            decimal(10,0) unsigned DEFAULT NULL,
  `discriminator_type`    varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_order_item_order`             (`order_id`),
  KEY `idx_order_item_product`           (`product_id`),
  KEY `idx_order_item_product_class`     (`product_class_id`),
  KEY `idx_order_item_shipping`          (`shipping_id`),
  KEY `idx_order_item_rounding_type`     (`rounding_type_id`),
  KEY `idx_order_item_tax_type`          (`tax_type_id`),
  KEY `idx_order_item_tax_display_type`  (`tax_display_type_id`),
  KEY `idx_order_item_order_item_type`   (`order_item_type_id`),
  CONSTRAINT `fk_order_item_order`            FOREIGN KEY (`order_id`)           REFERENCES `dtb_order` (`id`),
  CONSTRAINT `fk_order_item_product`          FOREIGN KEY (`product_id`)         REFERENCES `dtb_product` (`id`),
  CONSTRAINT `fk_order_item_product_class`    FOREIGN KEY (`product_class_id`)   REFERENCES `dtb_product_class` (`id`),
  CONSTRAINT `fk_order_item_shipping`         FOREIGN KEY (`shipping_id`)        REFERENCES `dtb_shipping` (`id`),
  CONSTRAINT `fk_order_item_rounding_type`    FOREIGN KEY (`rounding_type_id`)   REFERENCES `mtb_rounding_type` (`id`),
  CONSTRAINT `fk_order_item_tax_type`         FOREIGN KEY (`tax_type_id`)        REFERENCES `mtb_tax_type` (`id`),
  CONSTRAINT `fk_order_item_tax_display_type` FOREIGN KEY (`tax_display_type_id`) REFERENCES `mtb_tax_display_type` (`id`),
  CONSTRAINT `fk_order_item_order_item_type`  FOREIGN KEY (`order_item_type_id`) REFERENCES `mtb_order_item_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_mail_history (order mail log; referenced by var/sql order queries)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_mail_history` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id`           int(10) unsigned DEFAULT NULL,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `send_date`          datetime DEFAULT NULL,
  `mail_subject`       varchar(255) DEFAULT NULL,
  `mail_body`          longtext DEFAULT NULL,
  `mail_html_body`     longtext DEFAULT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mail_history_order`   (`order_id`),
  KEY `idx_mail_history_creator` (`creator_id`),
  CONSTRAINT `fk_mail_history_order`   FOREIGN KEY (`order_id`)   REFERENCES `dtb_order` (`id`),
  CONSTRAINT `fk_mail_history_creator` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_order_pdf
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_order_pdf` (
  `member_id`          int(10) unsigned NOT NULL,
  `title`              varchar(255) DEFAULT NULL,
  `message1`           varchar(255) DEFAULT NULL,
  `message2`           varchar(255) DEFAULT NULL,
  `message3`           varchar(255) DEFAULT NULL,
  `note1`              varchar(255) DEFAULT NULL,
  `note2`              varchar(255) DEFAULT NULL,
  `note3`              varchar(255) DEFAULT NULL,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `visible`            tinyint(1) NOT NULL DEFAULT 1,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_csv (CsvColumnConfigEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_csv` (
  `id`                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  `csv_type_id`          smallint(5) unsigned DEFAULT NULL,
  `creator_id`           int(10) unsigned DEFAULT NULL,
  `entity_name`          varchar(255) NOT NULL,
  `field_name`           varchar(255) NOT NULL,
  `reference_field_name` varchar(255) DEFAULT NULL,
  `disp_name`            varchar(255) NOT NULL,
  `sort_no`              smallint(5) unsigned NOT NULL,
  `enabled`              tinyint(1) NOT NULL DEFAULT 1,
  `create_date`          datetime NOT NULL,
  `update_date`          datetime NOT NULL,
  `discriminator_type`   varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_csv_type`    (`csv_type_id`),
  KEY `idx_csv_creator` (`creator_id`),
  CONSTRAINT `fk_csv_type`    FOREIGN KEY (`csv_type_id`) REFERENCES `mtb_csv_type` (`id`),
  CONSTRAINT `fk_csv_creator` FOREIGN KEY (`creator_id`)  REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_news (NewsEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_news` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id`         int(10) unsigned DEFAULT NULL,
  `publish_date`       datetime DEFAULT NULL,
  `title`              varchar(255) NOT NULL,
  `description`        longtext DEFAULT NULL,
  `url`                varchar(4000) DEFAULT NULL,
  `link_method`        tinyint(1) NOT NULL DEFAULT 0,
  `visible`            tinyint(1) NOT NULL DEFAULT 1,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_news_creator` (`creator_id`),
  CONSTRAINT `fk_news_creator` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_plugin (PluginEntity)
-- Phase 2b addition: UNIQUE KEY on code
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_plugin` (
  `id`                 int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name`               varchar(255) NOT NULL,
  `code`               varchar(255) NOT NULL,
  `enabled`            tinyint(1) NOT NULL DEFAULT 0,
  `version`            varchar(255) NOT NULL,
  `source`             varchar(255) NOT NULL,
  `initialized`        tinyint(1) NOT NULL DEFAULT 0,
  `create_date`        datetime NOT NULL,
  `update_date`        datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_plugin_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_tax_rule (TaxRuleEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_tax_rule` (
  `id`               int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_class_id` int(10) unsigned DEFAULT NULL,
  `creator_id`       int(10) unsigned DEFAULT NULL,
  `country_id`       smallint(5) unsigned DEFAULT NULL,
  `pref_id`          smallint(5) unsigned DEFAULT NULL,
  `product_id`       int(10) unsigned DEFAULT NULL,
  `rounding_type_id` smallint(5) unsigned DEFAULT NULL,
  `tax_rate`         decimal(10,0) unsigned NOT NULL DEFAULT 0,
  `tax_adjust`       decimal(10,0) unsigned NOT NULL DEFAULT 0,
  `apply_date`       datetime NOT NULL,
  `create_date`      datetime NOT NULL,
  `update_date`      datetime NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_tax_rule_product_class` (`product_class_id`),
  KEY `idx_tax_rule_creator`       (`creator_id`),
  KEY `idx_tax_rule_country`       (`country_id`),
  KEY `idx_tax_rule_pref`          (`pref_id`),
  KEY `idx_tax_rule_product`       (`product_id`),
  KEY `idx_tax_rule_rounding_type` (`rounding_type_id`),
  CONSTRAINT `fk_tax_rule_product_class` FOREIGN KEY (`product_class_id`) REFERENCES `dtb_product_class` (`id`),
  CONSTRAINT `fk_tax_rule_creator`       FOREIGN KEY (`creator_id`)       REFERENCES `dtb_member` (`id`),
  CONSTRAINT `fk_tax_rule_country`       FOREIGN KEY (`country_id`)       REFERENCES `mtb_country` (`id`),
  CONSTRAINT `fk_tax_rule_pref`          FOREIGN KEY (`pref_id`)          REFERENCES `mtb_pref` (`id`),
  CONSTRAINT `fk_tax_rule_product`       FOREIGN KEY (`product_id`)       REFERENCES `dtb_product` (`id`),
  CONSTRAINT `fk_tax_rule_rounding_type` FOREIGN KEY (`rounding_type_id`) REFERENCES `mtb_rounding_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ---------------------------------------------------------------------------
-- dtb_tradelaw (TradeLawEntity)
-- ---------------------------------------------------------------------------

CREATE TABLE `dtb_tradelaw` (
  `id`                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name`                 varchar(255) DEFAULT NULL,
  `description`          varchar(4000) DEFAULT NULL,
  `sort_no`              smallint(6) NOT NULL,
  `display_order_screen` tinyint(1) NOT NULL,
  `discriminator_type`   varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
