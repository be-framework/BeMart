INSERT INTO dtb_tag (id, name, sort_no, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:tag, '$.tagId') AS UNSIGNED), JSON_VALUE(:tag, '$.tagName'), 0, NOW(), NOW(), 'tag'
WHERE JSON_VALUE(:tag, '$.tagId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE name=VALUES(name), update_date=NOW()
