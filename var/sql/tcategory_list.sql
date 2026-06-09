SELECT CAST(id AS CHAR) AS id,
       category_name,
       CAST(parent_category_id AS CHAR) AS parent_category_id,
       sort_no
FROM dtb_category
ORDER BY sort_no ASC, id ASC
