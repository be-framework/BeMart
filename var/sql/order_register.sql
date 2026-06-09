INSERT INTO dtb_order (
  customer_id, payment_id, pre_order_id,
  order_no, name01, name02, subtotal,
  discount, delivery_fee_total, charge,
  tax, total, payment_total, add_point,
  use_point, order_status_id, order_date,
  payment_date, create_date, update_date,
  discriminator_type
)
SELECT
  CASE WHEN JSON_VALUE(
    CAST(:order AS CHAR),
    '$.customerId'
  ) REGEXP '^[0-9]+$' THEN
    CAST(
      JSON_VALUE(
        CAST(:order AS CHAR),
        '$.customerId'
      ) AS UNSIGNED
    )
  ELSE
    NULL
  END,
  NULLIF(
    CAST(
      JSON_VALUE(
        CAST(:order AS CHAR),
        '$.paymentMethodId'
      ) AS SIGNED
    ),
    0
  ),
  NULLIF(
    JSON_VALUE(
      CAST(:order AS CHAR),
      '$.preOrderId'
    ),
    ''
  ),
  JSON_VALUE(
    CAST(:order AS CHAR),
    '$.orderNo'
  ),
  '-',
  '-',
  CAST(
    JSON_VALUE(
      CAST(:order AS CHAR),
      '$.subtotal'
    ) AS SIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:order AS CHAR),
      '$.discount'
    ) AS SIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:order AS CHAR),
      '$.deliveryFeeTotal'
    ) AS SIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:order AS CHAR),
      '$.charge'
    ) AS SIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:order AS CHAR),
      '$.tax'
    ) AS SIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:order AS CHAR),
      '$.total'
    ) AS SIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:order AS CHAR),
      '$.paymentTotal'
    ) AS SIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:order AS CHAR),
      '$.addPoint'
    ) AS SIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:order AS CHAR),
      '$.usePoint'
    ) AS SIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:order AS CHAR),
      '$.orderStatus'
    ) AS SIGNED
  ),
  NULLIF(
    SUBSTRING(
      REPLACE(
        JSON_VALUE(
          CAST(:order AS CHAR),
          '$.orderDate'
        ),
        'T',
        ' '
      ),
      1,
      19
    ),
    ''
  ),
  NULLIF(
    SUBSTRING(
      REPLACE(
        JSON_VALUE(
          CAST(:order AS CHAR),
          '$.paymentDate'
        ),
        'T',
        ' '
      ),
      1,
      19
    ),
    ''
  ),
  NOW(),
  NOW(),
  'order' ON DUPLICATE KEY
UPDATE
  order_no =
VALUES
  (order_no),
  customer_id =
VALUES
  (customer_id),
  payment_id =
VALUES
  (payment_id),
  subtotal =
VALUES
  (subtotal),
  discount =
VALUES
  (discount),
  delivery_fee_total =
VALUES
  (delivery_fee_total),
  charge =
VALUES
  (charge),
  tax =
VALUES
  (tax),
  total =
VALUES
  (total),
  payment_total =
VALUES
  (payment_total),
  add_point =
VALUES
  (add_point),
  use_point =
VALUES
  (use_point),
  order_status_id =
VALUES
  (order_status_id),
  order_date =
VALUES
  (order_date),
  payment_date =
VALUES
  (payment_date),
  update_date = NOW()
