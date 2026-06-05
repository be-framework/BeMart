SELECT c.cart_key,
       c.sale_type_id,
       st.name AS sale_type_name,
       (SELECT COALESCE(CONCAT('[', GROUP_CONCAT(JSON_OBJECT(
           'productCode', pc.product_code,
           'quantity', ci.quantity,
           'price', ci.price,
           'productClassId', pc.id,
           'productId', p.id,
           'productName', p.name,
           'mainImage', (SELECT pi.file_name FROM dtb_product_image pi WHERE pi.product_id = p.id ORDER BY pi.sort_no ASC, pi.id ASC LIMIT 1),
           'classCategoryName1', cc1.name,
           'className1', cn1.name,
           'classCategoryName2', cc2.name,
           'className2', cn2.name
       ) ORDER BY ci.id ASC SEPARATOR ','), ']'), JSON_ARRAY())
        FROM dtb_cart_item ci
        INNER JOIN dtb_product_class pc ON pc.id = ci.product_class_id
        INNER JOIN dtb_product p ON p.id = pc.product_id
        LEFT JOIN dtb_class_category cc1 ON cc1.id = pc.class_category_id1
        LEFT JOIN dtb_class_name cn1 ON cn1.id = cc1.class_name_id
        LEFT JOIN dtb_class_category cc2 ON cc2.id = pc.class_category_id2
        LEFT JOIN dtb_class_name cn2 ON cn2.id = cc2.class_name_id
        WHERE ci.cart_id = c.id) AS items_json,
       c.total_price,
       c.delivery_fee_total,
       c.pre_order_id
FROM (
    SELECT cart.*, CAST(SUBSTRING_INDEX(cart.cart_key, '_', -1) AS UNSIGNED) AS sale_type_id
    FROM dtb_cart cart
    WHERE cart.cart_key = :cartKey
) c
LEFT JOIN mtb_sale_type st ON st.id = c.sale_type_id
LIMIT 1
