INSERT INTO dtb_news (id, title, description, url, publish_date, link_method, visible, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:news, '$.newsId') AS UNSIGNED), JSON_VALUE(:news, '$.newsTitle'), JSON_VALUE(:news, '$.newsDescription'), JSON_VALUE(:news, '$.newsUrl'), JSON_VALUE(:news, '$.publishDate'), CAST(JSON_VALUE(:news, '$.linkMethod') AS UNSIGNED), 1, NOW(), NOW(), 'news'
WHERE JSON_VALUE(:news, '$.newsId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), url=VALUES(url), publish_date=VALUES(publish_date), link_method=VALUES(link_method), update_date=NOW()
