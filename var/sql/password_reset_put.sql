UPDATE dtb_customer
SET reset_key = JSON_VALUE(:token, '$.resetKey'), reset_expire = JSON_VALUE(:token, '$.expiresAt'), update_date = NOW()
WHERE JSON_VALUE(:token, '$.customerId') REGEXP '^[0-9]+$' AND id = CAST(JSON_VALUE(:token, '$.customerId') AS UNSIGNED)
