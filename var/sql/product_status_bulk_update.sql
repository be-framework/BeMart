UPDATE
  dtb_product p
  INNER JOIN dtb_product_class pc ON pc.product_id = p.id
  AND pc.class_category_id1 IS NULL
  AND pc.class_category_id2 IS NULL
  INNER JOIN JSON_TABLE(
    :productCodes,
    '$[*]' COLUMNS (
      product_code VARCHAR(255) PATH '$'
    )
  ) AS codes ON codes.product_code = pc.product_code
SET
  p.product_status_id = :newStatus,
  p.update_date = NOW()
WHERE
  p.product_status_id <> :newStatus;

SELECT
  ROW_COUNT() AS changed_count
