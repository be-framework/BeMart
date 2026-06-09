DELETE FROM
  dtb_news
WHERE
  :newsId REGEXP '^[0-9]+$'
  AND id = CAST(:newsId AS UNSIGNED)
