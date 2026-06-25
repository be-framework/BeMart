SELECT
  CAST(MIN(fee) AS SIGNED) AS fee
FROM
  dtb_delivery_fee
WHERE
  :deliveryId REGEXP '^[0-9]+$'
  AND delivery_id = CAST(:deliveryId AS UNSIGNED)
HAVING
  COUNT(*) > 0
