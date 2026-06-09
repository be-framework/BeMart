INSERT INTO dtb_class_name (id, name, sort_no, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(CAST(:className AS CHAR), '$.classNameId') AS UNSIGNED), JSON_VALUE(CAST(:className AS CHAR), '$.name'), COALESCE((SELECT MAX(sort_no) + 1 FROM dtb_class_name), 1), NOW(), NOW(), 'classname'
WHERE JSON_VALUE(CAST(:className AS CHAR), '$.classNameId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE name=VALUES(name), update_date=NOW()
