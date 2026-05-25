DELETE FROM dtb_delivery WHERE :deliveryId REGEXP '^[0-9]+$' AND id = CAST(:deliveryId AS UNSIGNED)
