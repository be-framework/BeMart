INSERT INTO dtb_calendar (
  id, title, holiday, create_date, update_date,
  discriminator_type
)
SELECT
  CAST(
    JSON_VALUE(
      CAST(:calendar AS CHAR),
      '$.calendarId'
    ) AS UNSIGNED
  ),
  JSON_VALUE(
    CAST(:calendar AS CHAR),
    '$.title'
  ),
  CASE WHEN LENGTH(
    REPLACE(
      JSON_VALUE(
        CAST(:calendar AS CHAR),
        '$.holiday'
      ),
      'T',
      ' '
    )
  ) = 10 THEN
    CONCAT(
      REPLACE(
        JSON_VALUE(
          CAST(:calendar AS CHAR),
          '$.holiday'
        ),
        'T',
        ' '
      ),
      ' 00:00:00'
    )
  ELSE
    REPLACE(
      JSON_VALUE(
        CAST(:calendar AS CHAR),
        '$.holiday'
      ),
      'T',
      ' '
    )
  END,
  NOW(),
  NOW(),
  'calendar'
WHERE
  JSON_VALUE(
    CAST(:calendar AS CHAR),
    '$.calendarId'
  ) REGEXP '^[0-9]+$' ON DUPLICATE KEY
UPDATE
  title =
VALUES
  (title),
  holiday =
VALUES
  (holiday),
  update_date = NOW()
