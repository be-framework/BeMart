UPDATE
  dtb_class_category
SET
  visible = :visible,
  update_date = NOW()
WHERE
  :classCategoryId REGEXP '^[0-9]+$'
  AND id = CAST(:classCategoryId AS UNSIGNED)
