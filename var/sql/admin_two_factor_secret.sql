SELECT
  two_factor_auth_key
FROM
  dtb_member
WHERE
  login_id = :loginId
  AND two_factor_auth_enabled = 1
LIMIT
  1
