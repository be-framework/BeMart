SELECT t.name FROM dtb_product_tag pt INNER JOIN dtb_tag t ON t.id = pt.tag_id WHERE pt.product_id = :productId ORDER BY t.sort_no ASC, t.id ASC
