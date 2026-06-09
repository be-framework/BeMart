SELECT
  o.pre_order_id,
  o.customer_id,
  o.payment_id,
  o.delivery_fee_total,
  (
    SELECT
      COALESCE(
        CONCAT(
          '[',
          GROUP_CONCAT(
            JSON_OBJECT(
              'productCode', pc.product_code, 'productName',
              p.name, 'quantity', ci.quantity,
              'price', ci.price
            )
            ORDER BY
              ci.id ASC SEPARATOR ','
          ),
          ']'
        ),
        JSON_ARRAY()
      )
    FROM
      dtb_cart c
      INNER JOIN dtb_cart_item ci ON ci.cart_id = c.id
      INNER JOIN dtb_product_class pc ON pc.id = ci.product_class_id
      INNER JOIN dtb_product p ON p.id = pc.product_id
    WHERE
      c.pre_order_id = o.pre_order_id
  ) AS items_json
FROM
  dtb_order o
WHERE
  o.pre_order_id = :preOrderId
  AND o.order_status_id = 8
LIMIT
  1
