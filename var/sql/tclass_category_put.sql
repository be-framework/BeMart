INSERT INTO dtb_class_category (id, class_name_id, name, sort_no, visible, create_date, update_date, discriminator_type)
SELECT CAST(JSON_VALUE(:classCategory, '$.classCategoryId') AS UNSIGNED),
       CAST(JSON_VALUE(:classCategory, '$.classNameId') AS UNSIGNED),
       JSON_VALUE(:classCategory, '$.name'),
       COALESCE((SELECT MAX(sort_no) + 1 FROM dtb_class_category WHERE class_name_id = CAST(JSON_VALUE(:classCategory, '$.classNameId') AS UNSIGNED)), 1),
       1, NOW(), NOW(), 'classcategory'
WHERE JSON_VALUE(:classCategory, '$.classCategoryId') REGEXP '^[0-9]+$'
  AND JSON_VALUE(:classCategory, '$.classNameId') REGEXP '^[0-9]+$'
ON DUPLICATE KEY UPDATE class_name_id=VALUES(class_name_id), name=VALUES(name), update_date=NOW()
