UPDATE
  dtb_member
SET
  two_factor_auth_key = :secret,
  two_factor_auth_enabled = 1,
  update_date = NOW()
WHERE
  login_id = :loginId
