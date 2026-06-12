SELECT r.review_id AS reviewId,
       pc.product_code AS productCode,
       r.rating,
       r.title,
       r.body,
       r.reviewer,
       DATE_FORMAT(r.created_at, '%Y-%m-%d') AS createdAt
FROM dtb_product_review r
INNER JOIN dtb_product_class pc ON pc.product_id = r.product_id
WHERE pc.class_category_id1 IS NULL
  AND pc.class_category_id2 IS NULL
  AND pc.product_code = :productCode
ORDER BY r.created_at DESC, r.review_id DESC
LIMIT :limit OFFSET :offset
