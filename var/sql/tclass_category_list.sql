SELECT
  CAST(id AS CHAR) AS classCategoryId,
  CAST(class_name_id AS CHAR) AS classNameId,
  name
FROM
  dtb_class_category
ORDER BY
  id ASC
