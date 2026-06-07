SELECT CAST(id AS CHAR) AS id, login_id, password, COALESCE(name, '') AS name, COALESCE(authority_id, 0) AS authority_id, COALESCE(work_id, 1) AS work_id, COALESCE(sort_no, 0) AS sort_no
FROM dtb_member
WHERE (:nameKeyword IS NULL OR :nameKeyword = '' OR INSTR(name, :nameKeyword) > 0)
ORDER BY login_id ASC
LIMIT 50
