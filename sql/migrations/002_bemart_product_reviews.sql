-- IDEA STORE product reviews. EC-CUBE 4.3 core has no product-review table.

CREATE TABLE IF NOT EXISTS `dtb_product_review` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `review_id` varchar(64) NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `reviewer` varchar(255) NOT NULL,
  `created_at` date NOT NULL,
  `create_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `discriminator_type` varchar(255) NOT NULL DEFAULT 'product_review',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_bemart_product_review_review_id` (`review_id`),
  KEY `idx_bemart_product_review_product_created` (`product_id`, `created_at`, `review_id`),
  CONSTRAINT `fk_bemart_product_review_product` FOREIGN KEY (`product_id`) REFERENCES `dtb_product` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
