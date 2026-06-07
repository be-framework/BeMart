INSERT INTO dtb_tag (id, name, sort_no, discriminator_type)
SELECT CAST(JSON_VALUE(CAST(:tag AS CHAR), '$.tagId') AS UNSIGNED), JSON_VALUE(CAST(:tag AS CHAR), '$.tagName'), 0, 'tag'
WHERE JSON_VALUE(CAST(:tag AS CHAR), '$.tagId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE name=VALUES(name)
