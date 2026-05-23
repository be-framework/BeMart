INSERT INTO dtb_layout (id, device_type_id, layout_name, create_date, update_date, discriminator_type) VALUES (:id, :deviceType, :layoutName, NOW(), NOW(), 'layout')
