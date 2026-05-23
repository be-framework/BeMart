SELECT id, login_id, password, name, authority_id, work_id FROM dtb_member WHERE name LIKE :pattern ORDER BY login_id ASC
