UPDATE dtb_product SET product_status_id = :setStatus, update_date = NOW() WHERE id = :id AND (product_status_id IS NULL OR product_status_id <> :whereStatus)
