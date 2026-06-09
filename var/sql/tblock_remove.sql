DELETE FROM
  dtb_block_position
WHERE
  :blockId REGEXP '^[0-9]+$'
  AND block_id = CAST(:blockId AS UNSIGNED);

DELETE FROM
  dtb_block
WHERE
  :blockId REGEXP '^[0-9]+$'
  AND id = CAST(:blockId AS UNSIGNED)
