INSERT INTO dtb_login_history (login_history_status_id, login_id, client_ip, create_date, update_date, discriminator_type)
VALUES (CASE WHEN CAST(JSON_VALUE(:entry, '$.success') AS UNSIGNED) = 1 THEN 1 ELSE 2 END, JSON_VALUE(:entry, '$.loginId'), JSON_VALUE(:entry, '$.clientIp'), REPLACE(JSON_VALUE(:entry, '$.timestamp'), 'T', ' '), NOW(), 'loginhistory')
