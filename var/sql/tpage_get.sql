SELECT
  CAST(id AS CHAR),
  page_name,
  url,
  file_name,
  CAST(edit_type AS UNSIGNED)
FROM
  dtb_page
WHERE
  :pageId REGEXP '^[0-9]+$'
  AND id = CAST(:pageId AS UNSIGNED)
LIMIT
  1
