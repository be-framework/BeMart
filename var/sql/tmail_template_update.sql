UPDATE
  dtb_mail_template
SET
  mail_subject = JSON_VALUE(
    CAST(:entity AS CHAR),
    '$.subject'
  ),
  update_date = NOW()
WHERE
  id = CAST(
    JSON_VALUE(
      CAST(:entity AS CHAR),
      '$.mailTemplateId'
    ) AS UNSIGNED
  )
