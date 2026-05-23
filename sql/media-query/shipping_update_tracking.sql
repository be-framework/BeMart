UPDATE dtb_shipping
SET tracking_number = :trackingNumber,
    update_date = NOW()
WHERE id = :id
