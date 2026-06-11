INSERT INTO dtb_shipping (
  id, order_id, name01, name02, tracking_number,
  create_date, update_date, discriminator_type
)
SELECT
  existing.id,
  o.id,
  '',
  '',
  :trackingNumber,
  COALESCE(existing.create_date, NOW()),
  NOW(),
  'shipping'
FROM
  dtb_order o
  LEFT JOIN dtb_shipping existing ON existing.order_id = o.id
WHERE
  o.order_no = :orderNo
ORDER BY
  existing.id ASC
LIMIT
  1
ON DUPLICATE KEY UPDATE
  tracking_number = VALUES(tracking_number),
  update_date = VALUES(update_date),
  discriminator_type = VALUES(discriminator_type)
