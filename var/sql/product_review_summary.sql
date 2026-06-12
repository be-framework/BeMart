SELECT pc.product_code AS productCode,
       ROUND(AVG(r.rating), 1) AS averageRating,
       COUNT(*) AS reviewCount
FROM dtb_product_review r
INNER JOIN dtb_product_class pc ON pc.product_id = r.product_id
WHERE pc.class_category_id1 IS NULL
  AND pc.class_category_id2 IS NULL
  AND pc.product_code = :productCode
GROUP BY pc.product_code
