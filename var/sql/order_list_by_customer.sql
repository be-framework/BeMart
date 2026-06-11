SELECT
  order_no,
  pre_order_id,
  customer_id,
  payment_id,
  subtotal,
  delivery_fee_total,
  charge,
  discount,
  tax,
  total,
  payment_total,
  add_point,
  use_point,
  order_status_id,
  order_date,
  payment_date,
  JSON_OBJECT(
    'name01',
    name01,
    'name02',
    name02,
    'kana01',
    kana01,
    'kana02',
    kana02,
    'companyName',
    company_name,
    'email',
    email,
    'phoneNumber',
    phone_number,
    'postalCode',
    postal_code,
    'pref',
    pref_id,
    'addr01',
    addr01,
    'addr02',
    addr02
  ) AS customer_snapshot_json
FROM
  dtb_order
WHERE
  :customerId REGEXP '^[0-9]+$'
  AND customer_id = CAST(:customerId AS UNSIGNED)
  AND order_status_id <> 8
ORDER BY
  order_date DESC,
  id DESC
LIMIT
  :limit
OFFSET
  :offset
