SELECT CAST(id AS CHAR), block_name, file_name, deletable FROM dtb_block WHERE :blockId REGEXP '^[0-9]+$' AND id = CAST(:blockId AS UNSIGNED) LIMIT 1
