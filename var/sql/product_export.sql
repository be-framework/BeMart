SELECT
  b.product_code,
  b.product_name,
  b.price02,
  b.stock,
  b.product_status_id,
  b.description_detail,
  b.search_word,
  b.note,
  NULL AS image_file_name,
  JSON_ARRAY() AS category_names_json,
  JSON_ARRAY() AS tag_names_json,
  JSON_ARRAY() AS class_names_json
FROM
  (
    SELECT
      pc.id,
      pc.product_code,
      p.name AS product_name,
      pc.price02,
      pc.stock,
      p.product_status_id,
      p.description_detail,
      p.search_word,
      p.note
    FROM
      dtb_product_class pc FORCE INDEX (idx_bemart_pc_default_order)
      INNER JOIN dtb_product p ON p.id = pc.product_id
    WHERE
      pc.class_category_id1 IS NULL
      AND pc.class_category_id2 IS NULL
    ORDER BY
      pc.id ASC
    LIMIT
      :limit
    OFFSET
      :offset
  ) b
