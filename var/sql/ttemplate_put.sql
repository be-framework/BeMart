INSERT INTO dtb_template (
  id, device_type_id, template_code,
  template_name, create_date, update_date,
  discriminator_type
)
SELECT
  CAST(
    JSON_VALUE(
      CAST(:template AS CHAR),
      '$.templateId'
    ) AS UNSIGNED
  ),
  CAST(
    JSON_VALUE(
      CAST(:template AS CHAR),
      '$.deviceType'
    ) AS UNSIGNED
  ),
  :templateCode,
  JSON_VALUE(
    CAST(:template AS CHAR),
    '$.templateName'
  ),
  NOW(),
  NOW(),
  'template'
WHERE
  JSON_VALUE(
    CAST(:template AS CHAR),
    '$.templateId'
  ) REGEXP '^[0-9]+$' ON DUPLICATE KEY
UPDATE
  template_code =
VALUES
  (template_code),
  template_name =
VALUES
  (template_name),
  device_type_id =
VALUES
  (device_type_id),
  update_date = NOW()
