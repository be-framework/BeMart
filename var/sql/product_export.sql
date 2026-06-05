SELECT pc.product_code,
       p.name AS product_name,
       pc.price02,
       pc.stock,
       p.product_status_id,
       p.description_detail,
       p.search_word,
       p.note,
       (SELECT pi.file_name
        FROM dtb_product_image pi
        WHERE pi.product_id = p.id
        ORDER BY pi.sort_no ASC, pi.id ASC
        LIMIT 1) AS image_file_name,
       (SELECT COALESCE(CONCAT('[', GROUP_CONCAT(JSON_QUOTE(c.category_name) ORDER BY c.hierarchy ASC, c.sort_no DESC, c.id ASC SEPARATOR ','), ']'), JSON_ARRAY())
        FROM dtb_product_category pcat
        INNER JOIN dtb_category c ON c.id = pcat.category_id
        WHERE pcat.product_id = p.id) AS category_names_json,
       (SELECT COALESCE(CONCAT('[', GROUP_CONCAT(JSON_QUOTE(t.name) ORDER BY t.sort_no ASC, t.id ASC SEPARATOR ','), ']'), JSON_ARRAY())
        FROM dtb_product_tag pt
        INNER JOIN dtb_tag t ON t.id = pt.tag_id
        WHERE pt.product_id = p.id) AS tag_names_json,
       (SELECT COALESCE(CONCAT('[', GROUP_CONCAT(DISTINCT JSON_QUOTE(cn.name) ORDER BY cn.name SEPARATOR ','), ']'), JSON_ARRAY())
        FROM (
            SELECT cn1.name AS name, pc1.product_id
            FROM dtb_product_class pc1
            INNER JOIN dtb_class_category cc1 ON cc1.id = pc1.class_category_id1
            INNER JOIN dtb_class_name cn1 ON cn1.id = cc1.class_name_id
            UNION
            SELECT cn2.name AS name, pc2.product_id
            FROM dtb_product_class pc2
            INNER JOIN dtb_class_category cc2 ON cc2.id = pc2.class_category_id2
            INNER JOIN dtb_class_name cn2 ON cn2.id = cc2.class_name_id
        ) cn
        WHERE cn.product_id = p.id) AS class_names_json
FROM dtb_product_class pc
INNER JOIN dtb_product p ON p.id = pc.product_id
WHERE pc.class_category_id1 IS NULL
  AND pc.class_category_id2 IS NULL
ORDER BY pc.id ASC
