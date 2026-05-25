UPDATE dtb_shipping s
INNER JOIN dtb_order o ON o.id = s.order_id
SET s.tracking_number = :trackingNumber, s.update_date = NOW()
WHERE o.order_no = :orderNo;
INSERT INTO dtb_shipping (order_id, name01, name02, tracking_number, create_date, update_date, discriminator_type)
SELECT o.id, '', '', :trackingNumber, NOW(), NOW(), 'shipping'
FROM dtb_order o
WHERE o.order_no = :orderNo AND NOT EXISTS (SELECT 1 FROM dtb_shipping existing WHERE existing.order_id = o.id)
LIMIT 1
