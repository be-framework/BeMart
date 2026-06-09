DELETE FROM
  dtb_tag
WHERE
  :tagId REGEXP '^[0-9]+$'
  AND id = CAST(:tagId AS UNSIGNED)
