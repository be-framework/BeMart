SELECT CAST(id AS CHAR), login_id, password, COALESCE(name, ''), COALESCE(authority_id, 0), COALESCE(work_id, 1), sort_no FROM dtb_member ORDER BY login_id ASC LIMIT :limit OFFSET :offset
