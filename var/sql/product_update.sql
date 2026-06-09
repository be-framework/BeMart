UPDATE
  dtb_product p
  INNER JOIN dtb_product_class pc ON pc.product_id = p.id
  AND pc.class_category_id1 IS NULL
  AND pc.class_category_id2 IS NULL
SET
  p.name = JSON_VALUE(
    CAST(:product AS CHAR),
    '$.productName'
  ),
  p.product_status_id = CAST(
    JSON_VALUE(
      CAST(:product AS CHAR),
      '$.productStatus'
    ) AS UNSIGNED
  ),
  p.description_detail = JSON_VALUE(
    CAST(:product AS CHAR),
    '$.description'
  ),
  p.search_word = JSON_VALUE(
    CAST(:product AS CHAR),
    '$.searchWord'
  ),
  p.note = JSON_VALUE(
    CAST(:product AS CHAR),
    '$.note'
  ),
  p.update_date = NOW(),
  pc.price02 = CAST(
    JSON_VALUE(
      CAST(:product AS CHAR),
      '$.price02'
    ) AS SIGNED
  ),
  pc.stock = CAST(
    JSON_VALUE(
      CAST(:product AS CHAR),
      '$.stock'
    ) AS SIGNED
  ),
  pc.stock_unlimited = CASE WHEN JSON_VALUE(
    CAST(:product AS CHAR),
    '$.stock'
  ) IS NULL THEN
    1
  ELSE
    0
  END,
  pc.update_date = NOW()
WHERE
  pc.product_code = JSON_VALUE(
    CAST(:product AS CHAR),
    '$.productCode'
  )
