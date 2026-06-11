DELETE FROM
  dtb_calendar
WHERE
  :calendarId REGEXP '^[0-9]+$'
  AND id = CAST(:calendarId AS UNSIGNED)
