INSERT INTO dtb_product_class (
  id, product_id, product_code, stock, stock_unlimited,
  price02, delivery_fee, visible, create_date, update_date,
  discriminator_type
)
SELECT
  CAST(
    JSON_VALUE(
      CAST(:productClass AS CHAR),
      '$.productClassId'
    ) AS UNSIGNED
  ),
  (
    SELECT
      p.id
    FROM
      dtb_product AS p
    WHERE
      p.id = CAST(
        JSON_VALUE(
          CAST(:productClass AS CHAR),
          '$.productCode'
        ) AS UNSIGNED
      )
    LIMIT
      1
  ),
  JSON_VALUE(
    CAST(:productClass AS CHAR),
    '$.productCode'
  ),
  IF(
    LOWER(
      JSON_VALUE(
        CAST(:productClass AS CHAR),
        '$.stockUnlimited'
      )
    ) IN ('true', '1'),
    NULL,
    CAST(
      JSON_VALUE(
        CAST(:productClass AS CHAR),
        '$.stock'
      ) AS DECIMAL(10, 0)
    )
  ),
  IF(
    LOWER(
      JSON_VALUE(
        CAST(:productClass AS CHAR),
        '$.stockUnlimited'
      )
    ) IN ('true', '1'),
    1,
    0
  ),
  CAST(
    JSON_VALUE(
      CAST(:productClass AS CHAR),
      '$.price02'
    ) AS DECIMAL(12, 2)
  ),
  CAST(
    JSON_VALUE(
      CAST(:productClass AS CHAR),
      '$.deliveryFee'
    ) AS DECIMAL(12, 2)
  ),
  1,
  NOW(),
  NOW(),
  'productclass'
WHERE
  JSON_VALUE(
    CAST(:productClass AS CHAR),
    '$.productClassId'
  ) REGEXP '^[0-9]+$' ON DUPLICATE KEY
UPDATE
  product_code =
VALUES
  (product_code),
  stock =
VALUES
  (stock),
  stock_unlimited =
VALUES
  (stock_unlimited),
  price02 =
VALUES
  (price02),
  delivery_fee =
VALUES
  (delivery_fee),
  update_date = NOW()
