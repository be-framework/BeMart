-- sql/seed/analysis-sample.sql — representative operational data for SQL quality analysis.
--
-- Purpose
--   Koriym.SqlQuality runs `EXPLAIN FORMAT=JSON` / `EXPLAIN ANALYZE` against a LIVE
--   database. On empty `dtb_*` tables the optimizer sees zero cardinality and every
--   plan collapses to a trivial scan, so the analysis is meaningless. This seed loads
--   a representative *volume* of catalog + customer rows so EXPLAIN produces realistic
--   access paths (index lookups vs full scans) for the read-heavy SELECT queries in
--   `var/sql/` (product_get, product_list, product_search, customer_*, favorite_*, …).
--
-- Scope
--   Catalog subgraph (product / product_class / image / category / tag / class) plus
--   customers and admin members. Order / cart / config tables are intentionally left
--   empty for a later pass — their SELECTs still EXPLAIN validly (returning 0 rows).
--
-- Idempotent: TRUNCATEs the seeded tables, then regenerates. Run AFTER sql/setup-db.sh
-- (which loads schema + mtb_* master). Safe only against a scratch/test database.
--
-- Usage:
--   mysql --host 127.0.0.1 --port 3306 -u dbuser -psecret eccubedb_test < sql/seed/analysis-sample.sql

SET FOREIGN_KEY_CHECKS = 0;
SET SESSION cte_max_recursion_depth = 100000;

TRUNCATE TABLE dtb_product_tag;
TRUNCATE TABLE dtb_product_category;
TRUNCATE TABLE dtb_product_image;
TRUNCATE TABLE dtb_product_class;
TRUNCATE TABLE dtb_product;
TRUNCATE TABLE dtb_category;
TRUNCATE TABLE dtb_tag;
TRUNCATE TABLE dtb_class_category;
TRUNCATE TABLE dtb_class_name;
TRUNCATE TABLE dtb_customer;
TRUNCATE TABLE dtb_member;

-- ── Dimensions ───────────────────────────────────────────────────────────────

-- 5 class names
INSERT INTO dtb_class_name (id, name, sort_no, create_date, update_date, discriminator_type)
WITH RECURSIVE seq(n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 5)
SELECT n, CONCAT('規格', n), n, NOW(), NOW(), 'classname' FROM seq;

-- 20 class categories (4 per class name)
INSERT INTO dtb_class_category (id, class_name_id, name, sort_no, create_date, update_date, discriminator_type)
WITH RECURSIVE seq(n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 20)
SELECT n, ((n - 1) DIV 4) + 1, CONCAT('規格分類', n), n, NOW(), NOW(), 'classcategory' FROM seq;

-- 50 categories
INSERT INTO dtb_category (id, category_name, hierarchy, sort_no, create_date, update_date, discriminator_type)
WITH RECURSIVE seq(n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 50)
SELECT n, CONCAT('カテゴリ', n), ((n - 1) DIV 10) + 1, n, NOW(), NOW(), 'category' FROM seq;

-- 20 tags
INSERT INTO dtb_tag (id, name, sort_no, discriminator_type)
WITH RECURSIVE seq(n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 20)
SELECT n, CONCAT('タグ', n), n, 'tag' FROM seq;

-- ── Products (2,000) and their satellites ────────────────────────────────────

INSERT INTO dtb_product (id, name, product_status_id, description_detail, search_word, note, create_date, update_date, discriminator_type)
WITH RECURSIVE seq(n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 2000)
SELECT n, CONCAT('商品', LPAD(n, 5, '0')), 1 + (n % 2),
       CONCAT('説明文 ', n), CONCAT('keyword', n % 100), NULL,
       NOW(), NOW(), 'product'
FROM seq;

-- 1 product_class per product. product_code is the catalog lookup key.
INSERT INTO dtb_product_class (id, product_id, product_code, price02, stock, stock_unlimited, visible,
                               class_category_id1, class_category_id2, create_date, update_date, discriminator_type)
WITH RECURSIVE seq(n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 2000)
-- Half the products are "simple" (no class category) so class-less SELECTs
-- (e.g. product_search) return rows; the other half exercises the class joins.
SELECT n, n, CONCAT('CODE', LPAD(n, 6, '0')), 100.00 + (n % 9000),
       (n % 500), 0, 1,
       IF(n % 2 = 0, NULL, 1 + (n % 20)), NULL, NOW(), NOW(), 'productclass'
FROM seq;

INSERT INTO dtb_product_image (id, product_id, file_name, sort_no, create_date, discriminator_type)
WITH RECURSIVE seq(n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 2000)
SELECT n, n, CONCAT('product', n, '.jpg'), 1, NOW(), 'productimage' FROM seq;

INSERT INTO dtb_product_category (product_id, category_id, discriminator_type)
WITH RECURSIVE seq(n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 2000)
SELECT n, 1 + (n % 50), 'productcategory' FROM seq;

INSERT INTO dtb_product_tag (id, product_id, tag_id, create_date, discriminator_type)
WITH RECURSIVE seq(n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 2000)
SELECT n, n, 1 + (n % 20), NOW(), 'producttag' FROM seq;

-- ── Customers (500) and admin members (5) ────────────────────────────────────

INSERT INTO dtb_customer (id, name01, name02, email, password, salt, secret_key,
                          customer_status_id, create_date, update_date, discriminator_type)
WITH RECURSIVE seq(n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 500)
SELECT n, CONCAT('姓', n), CONCAT('名', n), CONCAT('user', n, '@example.com'),
       SHA2(CONCAT('pw', n), 256), 'salt', SHA2(CONCAT('secret', n), 256),
       2, NOW(), NOW(), 'customer'
FROM seq;

INSERT INTO dtb_member (id, login_id, password, salt, name, sort_no, create_date, update_date, discriminator_type)
WITH RECURSIVE seq(n) AS (SELECT 1 UNION ALL SELECT n + 1 FROM seq WHERE n < 5)
SELECT n, CONCAT('admin', n), SHA2(CONCAT('pw', n), 256), 'salt', CONCAT('管理者', n), n, NOW(), NOW(), 'member'
FROM seq;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'analysis-sample loaded' AS status,
       (SELECT COUNT(*) FROM dtb_product)       AS products,
       (SELECT COUNT(*) FROM dtb_product_class) AS product_classes,
       (SELECT COUNT(*) FROM dtb_customer)      AS customers;
