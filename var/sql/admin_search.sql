SELECT CAST(id AS CHAR), login_id, password, COALESCE(name, ''), COALESCE(authority_id, 0), COALESCE(work_id, 1), sort_no
FROM dtb_member
WHERE (:nameKeyword IS NULL OR :nameKeyword = '' OR INSTR(name, :nameKeyword) > 0)
ORDER BY login_id ASC
LIMIT 50
