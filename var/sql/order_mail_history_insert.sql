INSERT INTO dtb_mail_history (
  order_id, creator_id, send_date, mail_subject,
  mail_body, mail_html_body, discriminator_type
)
SELECT
  o.id,
  NULL,
  JSON_VALUE(
    CAST(:mailHistory AS CHAR),
    '$.sendDate'
  ),
  JSON_VALUE(
    CAST(:mailHistory AS CHAR),
    '$.mailSubject'
  ),
  JSON_VALUE(
    CAST(:mailHistory AS CHAR),
    '$.mailBody'
  ),
  NULL,
  'mail_history'
FROM
  dtb_order o
WHERE
  o.order_no = :orderNo
  AND o.order_status_id <> 8
LIMIT
  1
