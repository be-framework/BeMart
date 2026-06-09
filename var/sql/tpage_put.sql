INSERT INTO dtb_page (id, page_name, url, file_name, edit_type, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(CAST(:page AS CHAR), '$.pageId') AS UNSIGNED), JSON_VALUE(CAST(:page AS CHAR), '$.pageName'), JSON_VALUE(CAST(:page AS CHAR), '$.pageUrl'), JSON_VALUE(CAST(:page AS CHAR), '$.pageFileName'), CAST(JSON_VALUE(CAST(:page AS CHAR), '$.pageEditType') AS UNSIGNED), NOW(), NOW(), 'page'
WHERE JSON_VALUE(CAST(:page AS CHAR), '$.pageId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE page_name=VALUES(page_name), url=VALUES(url), file_name=VALUES(file_name), edit_type=VALUES(edit_type), update_date=NOW()
