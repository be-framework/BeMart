UPDATE dtb_delivery SET sort_no = :sortNo, update_date = NOW() WHERE :deliveryId REGEXP '^[0-9]+$' AND id = CAST(:deliveryId AS UNSIGNED)
