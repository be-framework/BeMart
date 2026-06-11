INSERT INTO dtb_shipping (
  id, order_id, pref_id, name01, name02,
  postal_code, addr01, addr02, phone_number,
  create_date, update_date, discriminator_type
)
SELECT
  existing.id,
  o.id,
  NULLIF(
    CAST(
      JSON_VALUE(
        CAST(:address AS CHAR),
        '$.pref'
      ) AS SIGNED
    ),
    0
  ),
  JSON_VALUE(
    CAST(:address AS CHAR),
    '$.name01'
  ),
  JSON_VALUE(
    CAST(:address AS CHAR),
    '$.name02'
  ),
  JSON_VALUE(
    CAST(:address AS CHAR),
    '$.postalCode'
  ),
  JSON_VALUE(
    CAST(:address AS CHAR),
    '$.addr01'
  ),
  JSON_VALUE(
    CAST(:address AS CHAR),
    '$.addr02'
  ),
  JSON_VALUE(
    CAST(:address AS CHAR),
    '$.phoneNumber'
  ),
  COALESCE(existing.create_date, NOW()),
  NOW(),
  'shipping'
FROM
  dtb_order o
  LEFT JOIN dtb_shipping existing ON existing.order_id = o.id
WHERE
  o.order_no = JSON_VALUE(
    CAST(:address AS CHAR),
    '$.orderNo'
  )
ORDER BY
  existing.id ASC
LIMIT
  1
ON DUPLICATE KEY UPDATE
  pref_id = VALUES(pref_id),
  name01 = VALUES(name01),
  name02 = VALUES(name02),
  postal_code = VALUES(postal_code),
  addr01 = VALUES(addr01),
  addr02 = VALUES(addr02),
  phone_number = VALUES(phone_number),
  update_date = VALUES(update_date),
  discriminator_type = VALUES(discriminator_type)
