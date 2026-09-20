SELECT
  CAST(id AS CHAR) AS productClassId,
  product_code AS productCode,
  CAST(price02 AS UNSIGNED) AS price02,
  CASE
    WHEN stock IS NULL THEN NULL
    ELSE CAST(stock AS UNSIGNED)
  END AS stock,
  stock_unlimited AS stockUnlimited,
  CAST(IFNULL(delivery_fee, 0) AS UNSIGNED) AS deliveryFee
FROM
  dtb_product_class
WHERE
  :productClassId REGEXP '^[0-9]+$'
  AND id = CAST(:productClassId AS UNSIGNED)
LIMIT
  1
