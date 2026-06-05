SELECT CAST(id AS CHAR), title, description, url, DATE_FORMAT(publish_date, '%Y-%m-%d %H:%i:%s'), link_method FROM dtb_news WHERE :newsId REGEXP '^[0-9]+$' AND id = CAST(:newsId AS UNSIGNED) LIMIT 1
