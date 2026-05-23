INSERT INTO dtb_block (id, block_name, file_name, deletable, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:block, '$.blockId') AS UNSIGNED), JSON_VALUE(:block, '$.blockName'), JSON_VALUE(:block, '$.blockFileName'), CAST(JSON_VALUE(:block, '$.blockDeletable') AS UNSIGNED), NOW(), NOW(), 'block'
WHERE JSON_VALUE(:block, '$.blockId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE block_name=VALUES(block_name), file_name=VALUES(file_name), deletable=VALUES(deletable), update_date=NOW()
