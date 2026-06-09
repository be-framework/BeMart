SELECT
  o.order_no,
  s.name01,
  s.name02,
  s.postal_code,
  s.pref_id,
  s.addr01,
  s.addr02,
  s.phone_number
FROM
  dtb_order o
  INNER JOIN dtb_shipping s ON s.order_id = o.id
WHERE
  o.order_no = :orderNo
ORDER BY
  s.id ASC
LIMIT
  1
