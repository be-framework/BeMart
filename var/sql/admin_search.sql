SELECT id, login_id, password, name, authority_id, work_id, sort_no
FROM dtb_member
WHERE (:nameKeyword IS NULL OR :nameKeyword = '' OR INSTR(name, :nameKeyword) > 0)
ORDER BY login_id ASC
LIMIT 50
