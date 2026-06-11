DELETE FROM
  dtb_class_name
WHERE
  :classNameId REGEXP '^[0-9]+$'
  AND id = CAST(:classNameId AS UNSIGNED)
