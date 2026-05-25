SELECT lh.create_date, lh.login_id, (lh.login_history_status_id = 1) AS success, lh.client_ip
FROM dtb_login_history lh
ORDER BY lh.create_date DESC, lh.id DESC
LIMIT :limit
