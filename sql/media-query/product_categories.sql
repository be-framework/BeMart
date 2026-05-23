SELECT c.category_name FROM dtb_product_category pc INNER JOIN dtb_category c ON c.id = pc.category_id WHERE pc.product_id = :productId ORDER BY c.hierarchy ASC, c.sort_no DESC, c.id ASC
