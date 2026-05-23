UPDATE dtb_order
SET order_status_id = :status,
    update_date = NOW()
WHERE order_no = :orderNo
