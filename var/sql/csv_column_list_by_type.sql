SELECT
  csv_type_id AS csvType,
  field_name AS columnName,
  enabled,
  sort_no AS sortNo
FROM
  dtb_csv
WHERE
  csv_type_id = :csvType
ORDER BY
  sort_no ASC,
  id ASC
