UPDATE dtb_plugin
SET enabled = :enabled,
    update_date = NOW()
WHERE code = :code
