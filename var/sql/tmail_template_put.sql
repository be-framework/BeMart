INSERT INTO dtb_mail_template (
  id, name, file_name, mail_subject,
  create_date, update_date, deletable, discriminator_type
)
SELECT
  CAST(
    JSON_VALUE(
      CAST(:entity AS CHAR),
      '$.mailTemplateId'
    ) AS UNSIGNED
  ),
  JSON_VALUE(
    CAST(:entity AS CHAR),
    '$.mailTemplateName'
  ),
  JSON_VALUE(
    CAST(:entity AS CHAR),
    '$.fileName'
  ),
  JSON_VALUE(
    CAST(:entity AS CHAR),
    '$.subject'
  ),
  NOW(),
  NOW(),
  COALESCE(
    CAST(
      JSON_VALUE(
        CAST(:entity AS CHAR),
        '$.deletable'
      ) AS UNSIGNED
    ),
    0
  ),
  'mail_template'
WHERE
  JSON_VALUE(
    CAST(:entity AS CHAR),
    '$.mailTemplateId'
  ) REGEXP '^[0-9]+$' ON DUPLICATE KEY
UPDATE
  name =
VALUES
  (name),
  file_name =
VALUES
  (file_name),
  mail_subject =
VALUES
  (mail_subject),
  deletable =
VALUES
  (deletable),
  update_date = NOW()
