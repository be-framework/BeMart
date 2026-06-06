SELECT CAST(id AS CHAR), login_id, password, COALESCE(name, ''), COALESCE(authority_id, 0), COALESCE(work_id, 1), sort_no FROM dtb_member WHERE login_id = :loginId LIMIT 1
