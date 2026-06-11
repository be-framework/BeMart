DELETE
  p,
  pl
FROM
  dtb_page p
  LEFT JOIN dtb_page_layout pl ON pl.page_id = p.id
WHERE
  :pageId REGEXP '^[0-9]+$'
  AND p.id = CAST(:pageId AS UNSIGNED)
