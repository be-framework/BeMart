SELECT id, reset_key, reset_expire FROM dtb_customer WHERE reset_key = :resetKey LIMIT 1
