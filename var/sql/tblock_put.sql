INSERT INTO dtb_block (id, block_name, file_name, deletable, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(CAST(:block AS CHAR), '$.blockId') AS UNSIGNED), JSON_VALUE(CAST(:block AS CHAR), '$.blockName'), JSON_VALUE(CAST(:block AS CHAR), '$.blockFileName'), IF(JSON_VALUE(CAST(:block AS CHAR), '$.blockDeletable') = 'true', 1, 0), NOW(), NOW(), 'block'
WHERE JSON_VALUE(CAST(:block AS CHAR), '$.blockId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE block_name=VALUES(block_name), file_name=VALUES(file_name), deletable=VALUES(deletable), update_date=NOW()
