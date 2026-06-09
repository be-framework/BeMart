UPDATE
  dtb_shipping s
  INNER JOIN dtb_order o ON o.id = s.order_id
SET
  s.pref_id = NULLIF(
    CAST(
      JSON_VALUE(
        CAST(:address AS CHAR),
        '$.pref'
      ) AS SIGNED
    ),
    0
  ),
  s.name01 = JSON_VALUE(
    CAST(:address AS CHAR),
    '$.name01'
  ),
  s.name02 = JSON_VALUE(
    CAST(:address AS CHAR),
    '$.name02'
  ),
  s.postal_code = JSON_VALUE(
    CAST(:address AS CHAR),
    '$.postalCode'
  ),
  s.addr01 = JSON_VALUE(
    CAST(:address AS CHAR),
    '$.addr01'
  ),
  s.addr02 = JSON_VALUE(
    CAST(:address AS CHAR),
    '$.addr02'
  ),
  s.phone_number = JSON_VALUE(
    CAST(:address AS CHAR),
    '$.phoneNumber'
  ),
  s.update_date = NOW()
WHERE
  o.order_no = JSON_VALUE(
    CAST(:address AS CHAR),
    '$.orderNo'
  );

INSERT INTO dtb_shipping (
  order_id, pref_id, name01, name02,
  postal_code, addr01, addr02, phone_number,
  create_date, update_date, discriminator_type
)
SELECT
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
  NOW(),
  NOW(),
  'shipping'
FROM
  dtb_order o
WHERE
  o.order_no = JSON_VALUE(
    CAST(:address AS CHAR),
    '$.orderNo'
  )
  AND NOT EXISTS (
    SELECT
      1
    FROM
      dtb_shipping existing
    WHERE
      existing.order_id = o.id
  )
LIMIT
  1
