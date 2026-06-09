DELETE FROM
  dtb_csv
WHERE
  csv_type_id = :csvType;

INSERT INTO dtb_csv (
  csv_type_id, entity_name, field_name,
  disp_name, sort_no, enabled, create_date,
  update_date, discriminator_type
)
SELECT
  :csvType,
  jt.column_name,
  jt.column_name,
  jt.column_name,
  jt.sort_no,
  jt.enabled,
  NOW(),
  NOW(),
  'csv'
FROM
  JSON_TABLE(
    :entries,
    '$[*]' COLUMNS (
      column_name VARCHAR(255) PATH '$.columnName',
      sort_no INT PATH '$.sortNo',
      enabled TINYINT PATH '$.enabled'
    )
  ) AS jt
