SET
  @bemart_product_copy_id = 0;

INSERT INTO dtb_product (
  product_status_id, name, note, description_detail,
  search_word, create_date, update_date,
  discriminator_type
)
SELECT
  1,
  CONCAT('(コピー) ', p.name),
  p.note,
  p.description_detail,
  p.search_word,
  NOW(),
  NOW(),
  'product'
FROM
  dtb_product p
  INNER JOIN dtb_product_class pc ON pc.product_id = p.id
  AND pc.class_category_id1 IS NULL
  AND pc.class_category_id2 IS NULL
WHERE
  pc.product_code = :sourceCode
LIMIT
  1;

SET
  @bemart_product_copy_id = IF(
    ROW_COUNT() > 0,
    LAST_INSERT_ID(),
    0
  );

INSERT INTO dtb_product_class (
  product_id, product_code, price02,
  stock, stock_unlimited, visible,
  create_date, update_date, discriminator_type
)
SELECT
  @bemart_product_copy_id,
  :newCode,
  pc.price02,
  pc.stock,
  pc.stock_unlimited,
  pc.visible,
  NOW(),
  NOW(),
  'productclass'
FROM
  dtb_product_class pc
WHERE
  pc.product_code = :sourceCode
  AND pc.class_category_id1 IS NULL
  AND pc.class_category_id2 IS NULL
  AND @bemart_product_copy_id <> 0
LIMIT
  1;

SELECT
  pc.product_code,
  p.name AS product_name,
  pc.price02,
  pc.stock,
  p.product_status_id,
  p.description_detail,
  p.search_word,
  p.note,
  (
    SELECT
      pi.file_name
    FROM
      dtb_product_image pi
    WHERE
      pi.product_id = p.id
    ORDER BY
      pi.sort_no ASC,
      pi.id ASC
    LIMIT
      1
  ) AS image_file_name,
  JSON_ARRAY() AS category_names_json,
  JSON_ARRAY() AS tag_names_json,
  JSON_ARRAY() AS class_names_json
FROM
  dtb_product_class pc
  INNER JOIN dtb_product p ON p.id = pc.product_id
WHERE
  pc.product_code = :newCode
  AND pc.class_category_id1 IS NULL
  AND pc.class_category_id2 IS NULL
LIMIT
  1;
