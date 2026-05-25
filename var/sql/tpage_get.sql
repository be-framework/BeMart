SELECT id, page_name, url, file_name, edit_type FROM dtb_page WHERE :pageId REGEXP '^[0-9]+$' AND id = CAST(:pageId AS UNSIGNED) LIMIT 1
