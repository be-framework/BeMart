SELECT
  CAST(ca.id AS CHAR) AS address_id,
  CAST(ca.customer_id AS CHAR) AS customer_id,
  ca.name01,
  ca.name02,
  ca.kana01,
  ca.kana02,
  ca.company_name,
  ca.phone_number,
  ca.postal_code,
  ca.pref_id,
  ca.addr01,
  ca.addr02,
  pref.name AS pref_name
FROM
  dtb_customer_address ca
  LEFT JOIN mtb_pref pref ON pref.id = ca.pref_id
WHERE
  :addressId REGEXP '^[0-9]+$'
  AND ca.id = CAST(:addressId AS UNSIGNED)
LIMIT
  1
