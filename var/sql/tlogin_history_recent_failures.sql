SELECT
  COUNT(*) AS failures
FROM
  dtb_login_history lh
WHERE
  lh.user_name = :loginId
  AND lh.client_ip = :clientIp
  AND lh.discriminator_type = 'loginhistory'
  AND lh.login_history_status_id = 0
  AND lh.create_date >= NOW() - INTERVAL :windowMinutes MINUTE
  AND lh.id > COALESCE(
    (
      SELECT
        MAX(ok.id)
      FROM
        dtb_login_history ok
      WHERE
        ok.user_name = :loginId
        AND ok.client_ip = :clientIp
        AND ok.discriminator_type = 'loginhistory'
        AND ok.login_history_status_id = 1
        AND ok.create_date >= NOW() - INTERVAL :windowMinutes MINUTE
    ),
    0
  )
