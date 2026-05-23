INSERT INTO dtb_login_history (member_id, login_history_status_id, login_id, client_ip, create_date, discriminator_type) VALUES (NULL, :statusId, :loginId, :clientIp, :created, 'login_history')
