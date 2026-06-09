SELECT
  CAST(id AS CHAR),
  block_name,
  file_name,
  deletable
FROM
  dtb_block
ORDER BY
  id ASC
