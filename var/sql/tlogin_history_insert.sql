INSERT INTO dtb_login_history (
  login_history_status_id, user_name,
  client_ip, create_date, update_date,
  discriminator_type
)
VALUES
  (
    CASE WHEN :success THEN 1 ELSE 0 END,
    :loginId,
    :clientIp,
    NOW(),
    NOW(),
    'loginhistory'
  )
