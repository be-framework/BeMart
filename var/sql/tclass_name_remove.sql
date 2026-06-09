DELETE FROM
  dtb_class_category
WHERE
  :classNameId REGEXP '^[0-9]+$'
  AND class_name_id = CAST(:classNameId AS UNSIGNED);

DELETE FROM
  dtb_class_name
WHERE
  :classNameId REGEXP '^[0-9]+$'
  AND id = CAST(:classNameId AS UNSIGNED)
