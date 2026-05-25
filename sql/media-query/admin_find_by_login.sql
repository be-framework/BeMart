SELECT id, login_id, password, name, authority_id, work_id FROM dtb_member WHERE login_id = :loginId LIMIT 1
