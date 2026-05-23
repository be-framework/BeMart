UPDATE dtb_plugin
SET initialized = 1,
    update_date = NOW()
WHERE code = :code
