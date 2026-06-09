DELETE FROM
  dtb_template
WHERE
  :templateId REGEXP '^[0-9]+$'
  AND id = CAST(:templateId AS UNSIGNED)
