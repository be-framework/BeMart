DELETE FROM dtb_page_layout WHERE :pageId REGEXP '^[0-9]+$' AND page_id = CAST(:pageId AS UNSIGNED);
DELETE FROM dtb_page WHERE :pageId REGEXP '^[0-9]+$' AND id = CAST(:pageId AS UNSIGNED)
