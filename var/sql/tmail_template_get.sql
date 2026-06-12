SELECT
  id,
  name,
  file_name,
  mail_subject,
  deletable
FROM
  dtb_mail_template
WHERE
  id = :mailTemplateId
LIMIT
  1
