DELETE FROM
  dtb_class_category
WHERE
  :classCategoryId REGEXP '^[0-9]+$'
  AND id = CAST(:classCategoryId AS UNSIGNED)
