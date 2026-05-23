UPDATE dtb_member SET login_id = :loginId, password = :password, name = :name, authority_id = :authority, work_id = :work, update_date = NOW() WHERE id = :id
