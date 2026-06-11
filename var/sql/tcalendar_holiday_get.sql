SELECT
  CAST(id AS CHAR),
  title,
  DATE_FORMAT(holiday, '%Y-%m-%d')
FROM
  dtb_calendar
WHERE
  :calendarId REGEXP '^[0-9]+$'
  AND id = CAST(:calendarId AS UNSIGNED)
LIMIT
  1
