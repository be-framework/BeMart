/*M!999999\- enable the sandbox mode */ 
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_authority_role` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `authority_id` smallint(5) unsigned DEFAULT NULL,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `deny_url` varchar(4000) NOT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4A1F70B181EC865B` (`authority_id`),
  KEY `IDX_4A1F70B161220EA6` (`creator_id`),
  CONSTRAINT `FK_4A1F70B161220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`),
  CONSTRAINT `FK_4A1F70B181EC865B` FOREIGN KEY (`authority_id`) REFERENCES `mtb_authority` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_base_info` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` smallint(5) unsigned DEFAULT NULL,
  `pref_id` smallint(5) unsigned DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `company_kana` varchar(255) DEFAULT NULL,
  `postal_code` varchar(8) DEFAULT NULL,
  `addr01` varchar(255) DEFAULT NULL,
  `addr02` varchar(255) DEFAULT NULL,
  `phone_number` varchar(14) DEFAULT NULL,
  `business_hour` varchar(255) DEFAULT NULL,
  `email01` varchar(255) DEFAULT NULL,
  `email02` varchar(255) DEFAULT NULL,
  `email03` varchar(255) DEFAULT NULL,
  `email04` varchar(255) DEFAULT NULL,
  `shop_name` varchar(255) DEFAULT NULL,
  `shop_kana` varchar(255) DEFAULT NULL,
  `shop_name_eng` varchar(255) DEFAULT NULL,
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `good_traded` varchar(4000) DEFAULT NULL,
  `message` varchar(4000) DEFAULT NULL,
  `delivery_free_amount` decimal(12,2) unsigned DEFAULT NULL,
  `delivery_free_quantity` int(10) unsigned DEFAULT NULL,
  `option_mypage_order_status_display` tinyint(1) NOT NULL DEFAULT 1,
  `option_nostock_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `option_favorite_product` tinyint(1) NOT NULL DEFAULT 1,
  `option_product_delivery_fee` tinyint(1) NOT NULL DEFAULT 0,
  `invoice_registration_number` varchar(255) DEFAULT NULL,
  `option_product_tax_rule` tinyint(1) NOT NULL DEFAULT 0,
  `option_customer_activate` tinyint(1) NOT NULL DEFAULT 1,
  `option_remember_me` tinyint(1) NOT NULL DEFAULT 1,
  `option_mail_notifier` tinyint(1) NOT NULL DEFAULT 0,
  `authentication_key` varchar(255) DEFAULT NULL,
  `php_path` varchar(255) DEFAULT NULL,
  `option_point` tinyint(1) NOT NULL DEFAULT 1,
  `basic_point_rate` decimal(10,0) unsigned DEFAULT 1,
  `point_conversion_rate` decimal(10,0) unsigned DEFAULT 1,
  `ga_id` varchar(255) DEFAULT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1D3655F4F92F3E70` (`country_id`),
  KEY `IDX_1D3655F4E171EF5F` (`pref_id`),
  CONSTRAINT `FK_1D3655F4E171EF5F` FOREIGN KEY (`pref_id`) REFERENCES `mtb_pref` (`id`),
  CONSTRAINT `FK_1D3655F4F92F3E70` FOREIGN KEY (`country_id`) REFERENCES `mtb_country` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_block` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `device_type_id` smallint(5) unsigned DEFAULT NULL,
  `block_name` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `use_controller` tinyint(1) NOT NULL DEFAULT 0,
  `deletable` tinyint(1) NOT NULL DEFAULT 1,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_type_id` (`device_type_id`,`file_name`),
  KEY `IDX_6B54DCBD4FFA550E` (`device_type_id`),
  CONSTRAINT `FK_6B54DCBD4FFA550E` FOREIGN KEY (`device_type_id`) REFERENCES `mtb_device_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_block_position` (
  `section` int(10) unsigned NOT NULL,
  `block_id` int(10) unsigned NOT NULL,
  `layout_id` int(10) unsigned NOT NULL,
  `block_row` int(10) unsigned DEFAULT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`section`,`block_id`,`layout_id`),
  KEY `IDX_35DCD731E9ED820C` (`block_id`),
  KEY `IDX_35DCD7318C22AA1A` (`layout_id`),
  CONSTRAINT `FK_35DCD7318C22AA1A` FOREIGN KEY (`layout_id`) REFERENCES `dtb_layout` (`id`),
  CONSTRAINT `FK_35DCD731E9ED820C` FOREIGN KEY (`block_id`) REFERENCES `dtb_block` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_calendar` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `holiday` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_cart` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `cart_key` varchar(255) DEFAULT NULL,
  `pre_order_id` varchar(255) DEFAULT NULL,
  `total_price` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `delivery_fee_total` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `sort_no` smallint(5) unsigned DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `add_point` decimal(12,0) unsigned NOT NULL DEFAULT 0,
  `use_point` decimal(12,0) unsigned NOT NULL DEFAULT 0,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dtb_cart_pre_order_id_idx` (`pre_order_id`),
  KEY `IDX_FC3C24F09395C3F3` (`customer_id`),
  KEY `dtb_cart_update_date_idx` (`update_date`),
  CONSTRAINT `FK_FC3C24F09395C3F3` FOREIGN KEY (`customer_id`) REFERENCES `dtb_customer` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_cart_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_class_id` int(10) unsigned DEFAULT NULL,
  `cart_id` int(10) unsigned DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity` decimal(10,0) NOT NULL DEFAULT 0,
  `point_rate` decimal(10,0) unsigned DEFAULT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B0228F7421B06187` (`product_class_id`),
  KEY `IDX_B0228F741AD5CDBF` (`cart_id`),
  CONSTRAINT `FK_B0228F741AD5CDBF` FOREIGN KEY (`cart_id`) REFERENCES `dtb_cart` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_B0228F7421B06187` FOREIGN KEY (`product_class_id`) REFERENCES `dtb_product_class` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_category_id` int(10) unsigned DEFAULT NULL,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `category_name` varchar(255) NOT NULL,
  `hierarchy` int(10) unsigned NOT NULL,
  `sort_no` int(11) NOT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5ED2C2B796A8F92` (`parent_category_id`),
  KEY `IDX_5ED2C2B61220EA6` (`creator_id`),
  CONSTRAINT `FK_5ED2C2B61220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`),
  CONSTRAINT `FK_5ED2C2B796A8F92` FOREIGN KEY (`parent_category_id`) REFERENCES `dtb_category` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_class_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `class_name_id` int(10) unsigned DEFAULT NULL,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `backend_name` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` int(10) unsigned NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9B0D1DBAB462FB2A` (`class_name_id`),
  KEY `IDX_9B0D1DBA61220EA6` (`creator_id`),
  CONSTRAINT `FK_9B0D1DBA61220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`),
  CONSTRAINT `FK_9B0D1DBAB462FB2A` FOREIGN KEY (`class_name_id`) REFERENCES `dtb_class_name` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_class_name` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `backend_name` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` int(10) unsigned NOT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_187C95AD61220EA6` (`creator_id`),
  CONSTRAINT `FK_187C95AD61220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_csv` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `csv_type_id` smallint(5) unsigned DEFAULT NULL,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `entity_name` varchar(255) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `reference_field_name` varchar(255) DEFAULT NULL,
  `disp_name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F55F48C3E8507796` (`csv_type_id`),
  KEY `IDX_F55F48C361220EA6` (`creator_id`),
  CONSTRAINT `FK_F55F48C361220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`),
  CONSTRAINT `FK_F55F48C3E8507796` FOREIGN KEY (`csv_type_id`) REFERENCES `mtb_csv_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_customer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_status_id` smallint(5) unsigned DEFAULT NULL,
  `sex_id` smallint(5) unsigned DEFAULT NULL,
  `job_id` smallint(5) unsigned DEFAULT NULL,
  `country_id` smallint(5) unsigned DEFAULT NULL,
  `pref_id` smallint(5) unsigned DEFAULT NULL,
  `name01` varchar(255) NOT NULL,
  `name02` varchar(255) NOT NULL,
  `kana01` varchar(255) DEFAULT NULL,
  `kana02` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `postal_code` varchar(8) DEFAULT NULL,
  `addr01` varchar(255) DEFAULT NULL,
  `addr02` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `phone_number` varchar(14) DEFAULT NULL,
  `birth` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `password` varchar(255) NOT NULL,
  `salt` varchar(255) DEFAULT NULL,
  `secret_key` varchar(255) NOT NULL,
  `first_buy_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `last_buy_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `buy_times` decimal(10,0) unsigned DEFAULT 0,
  `buy_total` decimal(12,2) unsigned DEFAULT 0.00,
  `note` varchar(4000) DEFAULT NULL,
  `reset_key` varchar(255) DEFAULT NULL,
  `reset_expire` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `point` decimal(12,0) NOT NULL DEFAULT 0,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `secret_key` (`secret_key`),
  KEY `IDX_8298BBE3C00AF8A7` (`customer_status_id`),
  KEY `IDX_8298BBE35A2DB2A0` (`sex_id`),
  KEY `IDX_8298BBE3BE04EA9` (`job_id`),
  KEY `IDX_8298BBE3F92F3E70` (`country_id`),
  KEY `IDX_8298BBE3E171EF5F` (`pref_id`),
  KEY `dtb_customer_buy_times_idx` (`buy_times`),
  KEY `dtb_customer_buy_total_idx` (`buy_total`),
  KEY `dtb_customer_create_date_idx` (`create_date`),
  KEY `dtb_customer_update_date_idx` (`update_date`),
  KEY `dtb_customer_last_buy_date_idx` (`last_buy_date`),
  KEY `dtb_customer_email_idx` (`email`),
  CONSTRAINT `FK_8298BBE35A2DB2A0` FOREIGN KEY (`sex_id`) REFERENCES `mtb_sex` (`id`),
  CONSTRAINT `FK_8298BBE3BE04EA9` FOREIGN KEY (`job_id`) REFERENCES `mtb_job` (`id`),
  CONSTRAINT `FK_8298BBE3C00AF8A7` FOREIGN KEY (`customer_status_id`) REFERENCES `mtb_customer_status` (`id`),
  CONSTRAINT `FK_8298BBE3E171EF5F` FOREIGN KEY (`pref_id`) REFERENCES `mtb_pref` (`id`),
  CONSTRAINT `FK_8298BBE3F92F3E70` FOREIGN KEY (`country_id`) REFERENCES `mtb_country` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_customer_address` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `country_id` smallint(5) unsigned DEFAULT NULL,
  `pref_id` smallint(5) unsigned DEFAULT NULL,
  `name01` varchar(255) NOT NULL,
  `name02` varchar(255) NOT NULL,
  `kana01` varchar(255) DEFAULT NULL,
  `kana02` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `postal_code` varchar(8) DEFAULT NULL,
  `addr01` varchar(255) DEFAULT NULL,
  `addr02` varchar(255) DEFAULT NULL,
  `phone_number` varchar(14) DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6C38C0F89395C3F3` (`customer_id`),
  KEY `IDX_6C38C0F8F92F3E70` (`country_id`),
  KEY `IDX_6C38C0F8E171EF5F` (`pref_id`),
  CONSTRAINT `FK_6C38C0F89395C3F3` FOREIGN KEY (`customer_id`) REFERENCES `dtb_customer` (`id`),
  CONSTRAINT `FK_6C38C0F8E171EF5F` FOREIGN KEY (`pref_id`) REFERENCES `mtb_pref` (`id`),
  CONSTRAINT `FK_6C38C0F8F92F3E70` FOREIGN KEY (`country_id`) REFERENCES `mtb_country` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_customer_favorite_product` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_ED6313839395C3F3` (`customer_id`),
  KEY `IDX_ED6313834584665A` (`product_id`),
  CONSTRAINT `FK_ED6313834584665A` FOREIGN KEY (`product_id`) REFERENCES `dtb_product` (`id`),
  CONSTRAINT `FK_ED6313839395C3F3` FOREIGN KEY (`customer_id`) REFERENCES `dtb_customer` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_delivery` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `sale_type_id` smallint(5) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `service_name` varchar(255) DEFAULT NULL,
  `description` varchar(4000) DEFAULT NULL,
  `confirm_url` varchar(4000) DEFAULT NULL,
  `sort_no` int(10) unsigned DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3420D9FA61220EA6` (`creator_id`),
  KEY `IDX_3420D9FAB0524E01` (`sale_type_id`),
  CONSTRAINT `FK_3420D9FA61220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`),
  CONSTRAINT `FK_3420D9FAB0524E01` FOREIGN KEY (`sale_type_id`) REFERENCES `mtb_sale_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_delivery_duration` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `duration` smallint(6) NOT NULL DEFAULT 0,
  `sort_no` int(10) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_delivery_fee` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_id` int(10) unsigned DEFAULT NULL,
  `pref_id` smallint(5) unsigned DEFAULT NULL,
  `fee` decimal(12,2) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_491552412136921` (`delivery_id`),
  KEY `IDX_4915524E171EF5F` (`pref_id`),
  CONSTRAINT `FK_491552412136921` FOREIGN KEY (`delivery_id`) REFERENCES `dtb_delivery` (`id`),
  CONSTRAINT `FK_4915524E171EF5F` FOREIGN KEY (`pref_id`) REFERENCES `mtb_pref` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_delivery_time` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `delivery_id` int(10) unsigned DEFAULT NULL,
  `delivery_time` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E80EE3A612136921` (`delivery_id`),
  CONSTRAINT `FK_E80EE3A612136921` FOREIGN KEY (`delivery_id`) REFERENCES `dtb_delivery` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_layout` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `device_type_id` smallint(5) unsigned DEFAULT NULL,
  `layout_name` varchar(255) DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_5A62AA7C4FFA550E` (`device_type_id`),
  CONSTRAINT `FK_5A62AA7C4FFA550E` FOREIGN KEY (`device_type_id`) REFERENCES `mtb_device_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_login_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login_history_status_id` smallint(5) unsigned NOT NULL,
  `member_id` int(10) unsigned DEFAULT NULL,
  `user_name` longtext DEFAULT NULL,
  `client_ip` longtext DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_6191DD4F9FA62FDD` (`login_history_status_id`),
  KEY `IDX_6191DD4F7597D3FE` (`member_id`),
  CONSTRAINT `FK_6191DD4F7597D3FE` FOREIGN KEY (`member_id`) REFERENCES `dtb_member` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_6191DD4F9FA62FDD` FOREIGN KEY (`login_history_status_id`) REFERENCES `mtb_login_history_status` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_mail_history` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned DEFAULT NULL,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `send_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `mail_subject` varchar(255) DEFAULT NULL,
  `mail_body` longtext DEFAULT NULL,
  `mail_html_body` longtext DEFAULT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4870AB118D9F6D38` (`order_id`),
  KEY `IDX_4870AB1161220EA6` (`creator_id`),
  CONSTRAINT `FK_4870AB1161220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`),
  CONSTRAINT `FK_4870AB118D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `dtb_order` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_mail_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `mail_subject` varchar(255) DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `deletable` tinyint(1) NOT NULL DEFAULT 0,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1CB16DB261220EA6` (`creator_id`),
  CONSTRAINT `FK_1CB16DB261220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_member` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `work_id` smallint(5) unsigned DEFAULT NULL,
  `authority_id` smallint(5) unsigned DEFAULT NULL,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `login_id` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `salt` varchar(255) DEFAULT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `two_factor_auth_key` varchar(255) DEFAULT NULL,
  `two_factor_auth_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `login_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_10BC3BE6BB3453DB` (`work_id`),
  KEY `IDX_10BC3BE681EC865B` (`authority_id`),
  KEY `IDX_10BC3BE661220EA6` (`creator_id`),
  CONSTRAINT `FK_10BC3BE661220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`),
  CONSTRAINT `FK_10BC3BE681EC865B` FOREIGN KEY (`authority_id`) REFERENCES `mtb_authority` (`id`),
  CONSTRAINT `FK_10BC3BE6BB3453DB` FOREIGN KEY (`work_id`) REFERENCES `mtb_work` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `publish_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `title` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `url` varchar(4000) DEFAULT NULL,
  `link_method` tinyint(1) NOT NULL DEFAULT 0,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EA4C351761220EA6` (`creator_id`),
  CONSTRAINT `FK_EA4C351761220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `country_id` smallint(5) unsigned DEFAULT NULL,
  `pref_id` smallint(5) unsigned DEFAULT NULL,
  `sex_id` smallint(5) unsigned DEFAULT NULL,
  `job_id` smallint(5) unsigned DEFAULT NULL,
  `payment_id` int(10) unsigned DEFAULT NULL,
  `device_type_id` smallint(5) unsigned DEFAULT NULL,
  `pre_order_id` varchar(255) DEFAULT NULL,
  `order_no` varchar(255) DEFAULT NULL,
  `message` varchar(4000) DEFAULT NULL,
  `name01` varchar(255) NOT NULL,
  `name02` varchar(255) NOT NULL,
  `kana01` varchar(255) DEFAULT NULL,
  `kana02` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone_number` varchar(14) DEFAULT NULL,
  `postal_code` varchar(8) DEFAULT NULL,
  `addr01` varchar(255) DEFAULT NULL,
  `addr02` varchar(255) DEFAULT NULL,
  `birth` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `subtotal` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `discount` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `delivery_fee_total` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `charge` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `tax` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `payment_total` decimal(12,2) unsigned NOT NULL DEFAULT 0.00,
  `payment_method` varchar(255) DEFAULT NULL,
  `note` varchar(4000) DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `order_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `payment_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `currency_code` varchar(255) DEFAULT NULL,
  `complete_message` longtext DEFAULT NULL,
  `complete_mail_message` longtext DEFAULT NULL,
  `add_point` decimal(12,0) unsigned NOT NULL DEFAULT 0,
  `use_point` decimal(12,0) unsigned NOT NULL DEFAULT 0,
  `order_status_id` smallint(5) unsigned DEFAULT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dtb_order_pre_order_id_idx` (`pre_order_id`),
  KEY `IDX_1D66D8079395C3F3` (`customer_id`),
  KEY `IDX_1D66D807F92F3E70` (`country_id`),
  KEY `IDX_1D66D807E171EF5F` (`pref_id`),
  KEY `IDX_1D66D8075A2DB2A0` (`sex_id`),
  KEY `IDX_1D66D807BE04EA9` (`job_id`),
  KEY `IDX_1D66D8074C3A3BB` (`payment_id`),
  KEY `IDX_1D66D8074FFA550E` (`device_type_id`),
  KEY `IDX_1D66D807D7707B45` (`order_status_id`),
  KEY `dtb_order_email_idx` (`email`),
  KEY `dtb_order_order_date_idx` (`order_date`),
  KEY `dtb_order_payment_date_idx` (`payment_date`),
  KEY `dtb_order_update_date_idx` (`update_date`),
  KEY `dtb_order_order_no_idx` (`order_no`),
  CONSTRAINT `FK_1D66D8074C3A3BB` FOREIGN KEY (`payment_id`) REFERENCES `dtb_payment` (`id`),
  CONSTRAINT `FK_1D66D8074FFA550E` FOREIGN KEY (`device_type_id`) REFERENCES `mtb_device_type` (`id`),
  CONSTRAINT `FK_1D66D8075A2DB2A0` FOREIGN KEY (`sex_id`) REFERENCES `mtb_sex` (`id`),
  CONSTRAINT `FK_1D66D8079395C3F3` FOREIGN KEY (`customer_id`) REFERENCES `dtb_customer` (`id`),
  CONSTRAINT `FK_1D66D807BE04EA9` FOREIGN KEY (`job_id`) REFERENCES `mtb_job` (`id`),
  CONSTRAINT `FK_1D66D807E171EF5F` FOREIGN KEY (`pref_id`) REFERENCES `mtb_pref` (`id`),
  CONSTRAINT `FK_1D66D807F92F3E70` FOREIGN KEY (`country_id`) REFERENCES `mtb_country` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_order_item` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `product_class_id` int(10) unsigned DEFAULT NULL,
  `shipping_id` int(10) unsigned DEFAULT NULL,
  `rounding_type_id` smallint(5) unsigned DEFAULT NULL,
  `tax_type_id` smallint(5) unsigned DEFAULT NULL,
  `tax_display_type_id` smallint(5) unsigned DEFAULT NULL,
  `order_item_type_id` smallint(5) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_code` varchar(255) DEFAULT NULL,
  `class_name1` varchar(255) DEFAULT NULL,
  `class_name2` varchar(255) DEFAULT NULL,
  `class_category_name1` varchar(255) DEFAULT NULL,
  `class_category_name2` varchar(255) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity` decimal(10,0) NOT NULL DEFAULT 0,
  `tax` decimal(10,0) NOT NULL DEFAULT 0,
  `tax_rate` decimal(10,0) unsigned NOT NULL DEFAULT 0,
  `tax_adjust` decimal(10,0) unsigned NOT NULL DEFAULT 0,
  `tax_rule_id` smallint(5) unsigned DEFAULT NULL,
  `currency_code` varchar(255) DEFAULT NULL,
  `processor_name` varchar(255) DEFAULT NULL,
  `point_rate` decimal(10,0) unsigned DEFAULT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A0C8C3ED8D9F6D38` (`order_id`),
  KEY `IDX_A0C8C3ED4584665A` (`product_id`),
  KEY `IDX_A0C8C3ED21B06187` (`product_class_id`),
  KEY `IDX_A0C8C3ED4887F3F8` (`shipping_id`),
  KEY `IDX_A0C8C3ED1BD5C574` (`rounding_type_id`),
  KEY `IDX_A0C8C3ED84042C99` (`tax_type_id`),
  KEY `IDX_A0C8C3EDA2505856` (`tax_display_type_id`),
  KEY `IDX_A0C8C3EDCAD13EAD` (`order_item_type_id`),
  CONSTRAINT `FK_A0C8C3ED1BD5C574` FOREIGN KEY (`rounding_type_id`) REFERENCES `mtb_rounding_type` (`id`),
  CONSTRAINT `FK_A0C8C3ED21B06187` FOREIGN KEY (`product_class_id`) REFERENCES `dtb_product_class` (`id`),
  CONSTRAINT `FK_A0C8C3ED4584665A` FOREIGN KEY (`product_id`) REFERENCES `dtb_product` (`id`),
  CONSTRAINT `FK_A0C8C3ED4887F3F8` FOREIGN KEY (`shipping_id`) REFERENCES `dtb_shipping` (`id`),
  CONSTRAINT `FK_A0C8C3ED84042C99` FOREIGN KEY (`tax_type_id`) REFERENCES `mtb_tax_type` (`id`),
  CONSTRAINT `FK_A0C8C3ED8D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `dtb_order` (`id`),
  CONSTRAINT `FK_A0C8C3EDA2505856` FOREIGN KEY (`tax_display_type_id`) REFERENCES `mtb_tax_display_type` (`id`),
  CONSTRAINT `FK_A0C8C3EDCAD13EAD` FOREIGN KEY (`order_item_type_id`) REFERENCES `mtb_order_item_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_order_pdf` (
  `member_id` int(10) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message1` varchar(255) DEFAULT NULL,
  `message2` varchar(255) DEFAULT NULL,
  `message3` varchar(255) DEFAULT NULL,
  `note1` varchar(255) DEFAULT NULL,
  `note2` varchar(255) DEFAULT NULL,
  `note3` varchar(255) DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_page` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `master_page_id` int(10) unsigned DEFAULT NULL,
  `page_name` varchar(255) DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `edit_type` smallint(5) unsigned NOT NULL DEFAULT 1,
  `author` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `keyword` varchar(255) DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `meta_robots` varchar(255) DEFAULT NULL,
  `meta_tags` varchar(4000) DEFAULT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_E3951A67D0618E8C` (`master_page_id`),
  KEY `dtb_page_url_idx` (`url`),
  CONSTRAINT `FK_E3951A67D0618E8C` FOREIGN KEY (`master_page_id`) REFERENCES `dtb_page` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_page_layout` (
  `page_id` int(10) unsigned NOT NULL,
  `layout_id` int(10) unsigned NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`page_id`,`layout_id`),
  KEY `IDX_F2799941C4663E4` (`page_id`),
  KEY `IDX_F27999418C22AA1A` (`layout_id`),
  CONSTRAINT `FK_F27999418C22AA1A` FOREIGN KEY (`layout_id`) REFERENCES `dtb_layout` (`id`),
  CONSTRAINT `FK_F2799941C4663E4` FOREIGN KEY (`page_id`) REFERENCES `dtb_page` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `payment_method` varchar(255) DEFAULT NULL,
  `charge` decimal(12,2) unsigned DEFAULT 0.00,
  `rule_max` decimal(12,2) unsigned DEFAULT NULL,
  `sort_no` smallint(5) unsigned DEFAULT NULL,
  `fixed` tinyint(1) NOT NULL DEFAULT 1,
  `payment_image` varchar(255) DEFAULT NULL,
  `rule_min` decimal(12,2) unsigned DEFAULT NULL,
  `method_class` varchar(255) DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7AFF628F61220EA6` (`creator_id`),
  CONSTRAINT `FK_7AFF628F61220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_payment_option` (
  `delivery_id` int(10) unsigned NOT NULL,
  `payment_id` int(10) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`delivery_id`,`payment_id`),
  KEY `IDX_5631540D12136921` (`delivery_id`),
  KEY `IDX_5631540D4C3A3BB` (`payment_id`),
  CONSTRAINT `FK_5631540D12136921` FOREIGN KEY (`delivery_id`) REFERENCES `dtb_delivery` (`id`),
  CONSTRAINT `FK_5631540D4C3A3BB` FOREIGN KEY (`payment_id`) REFERENCES `dtb_payment` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_plugin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `version` varchar(255) NOT NULL,
  `source` varchar(255) NOT NULL,
  `initialized` tinyint(1) NOT NULL DEFAULT 0,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_product` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `product_status_id` smallint(5) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `note` longtext DEFAULT NULL,
  `description_list` longtext DEFAULT NULL,
  `description_detail` longtext DEFAULT NULL,
  `search_word` longtext DEFAULT NULL,
  `free_area` longtext DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C49DE22F61220EA6` (`creator_id`),
  KEY `IDX_C49DE22F557B630` (`product_status_id`),
  CONSTRAINT `FK_C49DE22F557B630` FOREIGN KEY (`product_status_id`) REFERENCES `mtb_product_status` (`id`),
  CONSTRAINT `FK_C49DE22F61220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_product_category` (
  `product_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`product_id`,`category_id`),
  KEY `IDX_B05778914584665A` (`product_id`),
  KEY `IDX_B057789112469DE2` (`category_id`),
  CONSTRAINT `FK_B057789112469DE2` FOREIGN KEY (`category_id`) REFERENCES `dtb_category` (`id`),
  CONSTRAINT `FK_B05778914584665A` FOREIGN KEY (`product_id`) REFERENCES `dtb_product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_product_class` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned DEFAULT NULL,
  `sale_type_id` smallint(5) unsigned DEFAULT NULL,
  `class_category_id1` int(10) unsigned DEFAULT NULL,
  `class_category_id2` int(10) unsigned DEFAULT NULL,
  `delivery_duration_id` int(10) unsigned DEFAULT NULL,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `product_code` varchar(255) DEFAULT NULL,
  `stock` decimal(10,0) DEFAULT NULL,
  `stock_unlimited` tinyint(1) NOT NULL DEFAULT 0,
  `sale_limit` decimal(10,0) unsigned DEFAULT NULL,
  `price01` decimal(12,2) DEFAULT NULL,
  `price02` decimal(12,2) NOT NULL,
  `delivery_fee` decimal(12,2) unsigned DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `currency_code` varchar(255) DEFAULT NULL,
  `point_rate` decimal(10,0) unsigned DEFAULT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1A11D1BA4584665A` (`product_id`),
  KEY `IDX_1A11D1BAB0524E01` (`sale_type_id`),
  KEY `IDX_1A11D1BA248D128` (`class_category_id1`),
  KEY `IDX_1A11D1BA9B418092` (`class_category_id2`),
  KEY `IDX_1A11D1BABA4269E` (`delivery_duration_id`),
  KEY `IDX_1A11D1BA61220EA6` (`creator_id`),
  KEY `dtb_product_class_price02_idx` (`price02`),
  KEY `dtb_product_class_stock_stock_unlimited_idx` (`stock`,`stock_unlimited`),
  CONSTRAINT `FK_1A11D1BA248D128` FOREIGN KEY (`class_category_id1`) REFERENCES `dtb_class_category` (`id`),
  CONSTRAINT `FK_1A11D1BA4584665A` FOREIGN KEY (`product_id`) REFERENCES `dtb_product` (`id`),
  CONSTRAINT `FK_1A11D1BA61220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`),
  CONSTRAINT `FK_1A11D1BA9B418092` FOREIGN KEY (`class_category_id2`) REFERENCES `dtb_class_category` (`id`),
  CONSTRAINT `FK_1A11D1BAB0524E01` FOREIGN KEY (`sale_type_id`) REFERENCES `mtb_sale_type` (`id`),
  CONSTRAINT `FK_1A11D1BABA4269E` FOREIGN KEY (`delivery_duration_id`) REFERENCES `dtb_delivery_duration` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_product_image` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned DEFAULT NULL,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_3267CC7A4584665A` (`product_id`),
  KEY `IDX_3267CC7A61220EA6` (`creator_id`),
  CONSTRAINT `FK_3267CC7A4584665A` FOREIGN KEY (`product_id`) REFERENCES `dtb_product` (`id`),
  CONSTRAINT `FK_3267CC7A61220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_product_stock` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_class_id` int(10) unsigned DEFAULT NULL,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `stock` decimal(10,0) DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BC6C9E4521B06187` (`product_class_id`),
  KEY `IDX_BC6C9E4561220EA6` (`creator_id`),
  CONSTRAINT `FK_BC6C9E4521B06187` FOREIGN KEY (`product_class_id`) REFERENCES `dtb_product_class` (`id`),
  CONSTRAINT `FK_BC6C9E4561220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_product_tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned DEFAULT NULL,
  `tag_id` int(10) unsigned DEFAULT NULL,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4433E7214584665A` (`product_id`),
  KEY `IDX_4433E721BAD26311` (`tag_id`),
  KEY `IDX_4433E72161220EA6` (`creator_id`),
  CONSTRAINT `FK_4433E7214584665A` FOREIGN KEY (`product_id`) REFERENCES `dtb_product` (`id`),
  CONSTRAINT `FK_4433E72161220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`),
  CONSTRAINT `FK_4433E721BAD26311` FOREIGN KEY (`tag_id`) REFERENCES `dtb_tag` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_shipping` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned DEFAULT NULL,
  `country_id` smallint(5) unsigned DEFAULT NULL,
  `pref_id` smallint(5) unsigned DEFAULT NULL,
  `delivery_id` int(10) unsigned DEFAULT NULL,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `name01` varchar(255) NOT NULL,
  `name02` varchar(255) NOT NULL,
  `kana01` varchar(255) DEFAULT NULL,
  `kana02` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `phone_number` varchar(14) DEFAULT NULL,
  `postal_code` varchar(8) DEFAULT NULL,
  `addr01` varchar(255) DEFAULT NULL,
  `addr02` varchar(255) DEFAULT NULL,
  `delivery_name` varchar(255) DEFAULT NULL,
  `time_id` int(10) unsigned DEFAULT NULL,
  `delivery_time` varchar(255) DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `shipping_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `tracking_number` varchar(255) DEFAULT NULL,
  `note` varchar(4000) DEFAULT NULL,
  `sort_no` smallint(5) unsigned DEFAULT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `mail_send_date` datetime DEFAULT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_2EBD22CE8D9F6D38` (`order_id`),
  KEY `IDX_2EBD22CEF92F3E70` (`country_id`),
  KEY `IDX_2EBD22CEE171EF5F` (`pref_id`),
  KEY `IDX_2EBD22CE12136921` (`delivery_id`),
  KEY `IDX_2EBD22CE61220EA6` (`creator_id`),
  CONSTRAINT `FK_2EBD22CE12136921` FOREIGN KEY (`delivery_id`) REFERENCES `dtb_delivery` (`id`),
  CONSTRAINT `FK_2EBD22CE61220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`),
  CONSTRAINT `FK_2EBD22CE8D9F6D38` FOREIGN KEY (`order_id`) REFERENCES `dtb_order` (`id`),
  CONSTRAINT `FK_2EBD22CEE171EF5F` FOREIGN KEY (`pref_id`) REFERENCES `mtb_pref` (`id`),
  CONSTRAINT `FK_2EBD22CEF92F3E70` FOREIGN KEY (`country_id`) REFERENCES `mtb_country` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_tag` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_tax_rule` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_class_id` int(10) unsigned DEFAULT NULL,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `country_id` smallint(5) unsigned DEFAULT NULL,
  `pref_id` smallint(5) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `rounding_type_id` smallint(5) unsigned DEFAULT NULL,
  `tax_rate` decimal(10,0) unsigned NOT NULL DEFAULT 0,
  `tax_adjust` decimal(10,0) unsigned NOT NULL DEFAULT 0,
  `apply_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_59F696DE21B06187` (`product_class_id`),
  KEY `IDX_59F696DE61220EA6` (`creator_id`),
  KEY `IDX_59F696DEF92F3E70` (`country_id`),
  KEY `IDX_59F696DEE171EF5F` (`pref_id`),
  KEY `IDX_59F696DE4584665A` (`product_id`),
  KEY `IDX_59F696DE1BD5C574` (`rounding_type_id`),
  CONSTRAINT `FK_59F696DE1BD5C574` FOREIGN KEY (`rounding_type_id`) REFERENCES `mtb_rounding_type` (`id`),
  CONSTRAINT `FK_59F696DE21B06187` FOREIGN KEY (`product_class_id`) REFERENCES `dtb_product_class` (`id`),
  CONSTRAINT `FK_59F696DE4584665A` FOREIGN KEY (`product_id`) REFERENCES `dtb_product` (`id`),
  CONSTRAINT `FK_59F696DE61220EA6` FOREIGN KEY (`creator_id`) REFERENCES `dtb_member` (`id`),
  CONSTRAINT `FK_59F696DEE171EF5F` FOREIGN KEY (`pref_id`) REFERENCES `mtb_pref` (`id`),
  CONSTRAINT `FK_59F696DEF92F3E70` FOREIGN KEY (`country_id`) REFERENCES `mtb_country` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `device_type_id` smallint(5) unsigned DEFAULT NULL,
  `template_code` varchar(255) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `create_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `update_date` datetime NOT NULL COMMENT '(DC2Type:datetimetz)',
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_94C12A694FFA550E` (`device_type_id`),
  CONSTRAINT `FK_94C12A694FFA550E` FOREIGN KEY (`device_type_id`) REFERENCES `mtb_device_type` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dtb_tradelaw` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(4000) DEFAULT NULL,
  `sort_no` smallint(6) NOT NULL,
  `display_order_screen` tinyint(1) NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_authority` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_country` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_csv_type` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_customer_order_status` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_customer_status` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_device_type` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_job` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_login_history_status` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_order_item_type` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_order_status` (
  `id` smallint(5) unsigned NOT NULL,
  `display_order_count` tinyint(1) NOT NULL DEFAULT 0,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_order_status_color` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_page_max` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_pref` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_product_list_max` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_product_list_order_by` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_product_status` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_rounding_type` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_sale_type` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_sex` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_tax_display_type` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_tax_type` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mtb_work` (
  `id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `sort_no` smallint(5) unsigned NOT NULL,
  `discriminator_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
