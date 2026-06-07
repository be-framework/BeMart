SELECT CAST(id AS CHAR) AS tagId, name AS tagName FROM dtb_tag WHERE :tagId REGEXP '^[0-9]+$' AND id = CAST(:tagId AS UNSIGNED) LIMIT 1
