UPDATE
  dtb_class_category
SET
  sort_no = :sortNo,
  update_date = NOW()
WHERE
  :classCategoryId REGEXP '^[0-9]+$'
  AND id = CAST(:classCategoryId AS UNSIGNED)
