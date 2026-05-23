UPDATE dtb_block SET block_name = :blockName, file_name = :fileName, deletable = :deletable, update_date = NOW() WHERE id = :id
