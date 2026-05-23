SELECT id, title, description, url, publish_date, link_method FROM dtb_news WHERE :newsId REGEXP '^[0-9]+$' AND id = CAST(:newsId AS UNSIGNED) LIMIT 1
