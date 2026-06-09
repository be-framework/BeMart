DELETE FROM
  dtb_product_category
WHERE
  :categoryId REGEXP '^[0-9]+$'
  AND category_id = CAST(:categoryId AS UNSIGNED);

DELETE FROM
  dtb_category
WHERE
  :categoryId REGEXP '^[0-9]+$'
  AND id = CAST(:categoryId AS UNSIGNED)
