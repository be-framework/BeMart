SELECT
  o.order_no,
  s.name01,
  s.name02,
  s.postal_code,
  s.pref_id,
  s.addr01,
  s.addr02,
  s.phone_number,
  COALESCE(s.tracking_number, '') AS tracking_number
FROM
  dtb_shipping s
  INNER JOIN dtb_order o ON o.id = s.order_id
ORDER BY
  s.id ASC
