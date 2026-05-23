INSERT INTO dtb_category (id, category_name, parent_category_id, sort_no, hierarchy, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:category, '$.categoryId') AS UNSIGNED),
       JSON_VALUE(:category, '$.categoryName'),
       CASE WHEN JSON_VALUE(:category, '$.parentId') REGEXP '^[0-9]+$' THEN CAST(JSON_VALUE(:category, '$.parentId') AS UNSIGNED) ELSE NULL END,
       CAST(JSON_VALUE(:category, '$.sortNo') AS SIGNED),
       CASE WHEN JSON_VALUE(:category, '$.parentId') REGEXP '^[0-9]+$'
            THEN COALESCE((SELECT parent.hierarchy + 1 FROM dtb_category parent WHERE parent.id = CAST(JSON_VALUE(:category, '$.parentId') AS UNSIGNED)), 1)
            ELSE 1 END,
       NOW(), NOW(), 'category'
WHERE JSON_VALUE(:category, '$.categoryId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE category_name=VALUES(category_name), parent_category_id=VALUES(parent_category_id), sort_no=VALUES(sort_no), hierarchy=VALUES(hierarchy), update_date=NOW()
