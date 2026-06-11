DELETE b,
  bp
FROM
  dtb_block b
  LEFT JOIN dtb_block_position bp ON bp.block_id = b.id
WHERE
  :blockId REGEXP '^[0-9]+$'
  AND b.id = CAST(:blockId AS UNSIGNED)
