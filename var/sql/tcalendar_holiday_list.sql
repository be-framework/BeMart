SELECT
  CAST(id AS CHAR),
  title,
  DATE_FORMAT(holiday, '%Y-%m-%d')
FROM
  dtb_calendar
ORDER BY
  holiday ASC,
  id ASC
