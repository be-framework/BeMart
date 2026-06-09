UPDATE
  dtb_delivery
SET
  visible = :visible,
  update_date = NOW()
WHERE
  :deliveryId REGEXP '^[0-9]+$'
  AND id = CAST(:deliveryId AS UNSIGNED)
