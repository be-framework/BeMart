INSERT INTO dtb_news (id, title, description, url, publish_date, link_method, visible, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(CAST(:news AS CHAR), '$.newsId') AS UNSIGNED), JSON_VALUE(CAST(:news AS CHAR), '$.newsTitle'), JSON_VALUE(CAST(:news AS CHAR), '$.newsDescription'), JSON_VALUE(CAST(:news AS CHAR), '$.newsUrl'), JSON_VALUE(CAST(:news AS CHAR), '$.publishDate'), IF(LOWER(JSON_VALUE(CAST(:news AS CHAR), '$.linkMethod')) IN ('true', '1'), 1, 0), 1, NOW(), NOW(), 'news'
WHERE JSON_VALUE(CAST(:news AS CHAR), '$.newsId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), url=VALUES(url), publish_date=VALUES(publish_date), link_method=VALUES(link_method), update_date=NOW()
