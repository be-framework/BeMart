SELECT
  CAST(id AS CHAR) AS timeId,
  delivery_time AS deliveryTime,
  visible
FROM
  dtb_delivery_time
WHERE
  :deliveryId REGEXP '^[0-9]+$'
  AND delivery_id = CAST(:deliveryId AS UNSIGNED)
  AND visible = 1
ORDER BY
  sort_no ASC
