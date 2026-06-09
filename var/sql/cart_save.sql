SET
  @bemart_cart_missing_codes = (
    SELECT
      COALESCE(
        GROUP_CONCAT(
          jt.product_code
          ORDER BY
            jt.product_code SEPARATOR ','
        ),
        ''
      )
    FROM
      JSON_TABLE(
        JSON_EXTRACT(
          CAST(:cart AS CHAR),
          '$.items'
        ),
        '$[*]' COLUMNS (
          product_code VARCHAR(255) PATH '$.productCode'
        )
      ) AS jt
      LEFT JOIN dtb_product_class pc ON pc.product_code = jt.product_code
      AND pc.class_category_id1 IS NULL
      AND pc.class_category_id2 IS NULL
    WHERE
      pc.id IS NULL
  );

DELETE FROM
  dtb_cart
WHERE
  @bemart_cart_missing_codes = ''
  AND cart_key = JSON_VALUE(
    CAST(:cart AS CHAR),
    '$.cartKey'
  );

INSERT INTO dtb_cart (
  cart_key, pre_order_id, total_price,
  delivery_fee_total, create_date,
  update_date, discriminator_type
)
SELECT
  JSON_VALUE(
    CAST(:cart AS CHAR),
    '$.cartKey'
  ),
  NULLIF(
    JSON_VALUE(
      CAST(:cart AS CHAR),
      '$.preOrderId'
    ),
    ''
  ),
  CAST(
    JSON_VALUE(
      CAST(:cart AS CHAR),
      '$.totalPrice'
    ) AS SIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:cart AS CHAR),
      '$.deliveryFeeTotal'
    ) AS SIGNED
  ),
  NOW(),
  NOW(),
  'cart'
WHERE
  @bemart_cart_missing_codes = '';

SET
  @bemart_cart_id = LAST_INSERT_ID();

INSERT INTO dtb_cart_item (
  product_class_id, cart_id, price,
  quantity, discriminator_type
)
SELECT
  pc.id,
  @bemart_cart_id,
  jt.price,
  jt.quantity,
  'cartitem'
FROM
  JSON_TABLE(
    JSON_EXTRACT(
      CAST(:cart AS CHAR),
      '$.items'
    ),
    '$[*]' COLUMNS (
      product_code VARCHAR(255) PATH '$.productCode',
      quantity INT PATH '$.quantity',
      price INT PATH '$.price'
    )
  ) AS jt
  INNER JOIN dtb_product_class pc ON pc.product_code = jt.product_code
  AND pc.class_category_id1 IS NULL
  AND pc.class_category_id2 IS NULL
WHERE
  @bemart_cart_missing_codes = '';

SELECT
  @bemart_cart_missing_codes AS missing_codes;
