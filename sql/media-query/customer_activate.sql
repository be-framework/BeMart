UPDATE dtb_customer SET customer_status_id = 2, update_date = NOW() WHERE :customerId REGEXP '^[0-9]+$' AND id = CAST(:customerId AS UNSIGNED)
