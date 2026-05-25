INSERT INTO dtb_page (id, page_name, url, file_name, edit_type, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:page, '$.pageId') AS UNSIGNED), JSON_VALUE(:page, '$.pageName'), JSON_VALUE(:page, '$.pageUrl'), JSON_VALUE(:page, '$.pageFileName'), CAST(JSON_VALUE(:page, '$.pageEditType') AS UNSIGNED), NOW(), NOW(), 'page'
WHERE JSON_VALUE(:page, '$.pageId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE page_name=VALUES(page_name), url=VALUES(url), file_name=VALUES(file_name), edit_type=VALUES(edit_type), update_date=NOW()
