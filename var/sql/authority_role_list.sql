SELECT
  authority_id AS authority,
  deny_url AS denyUrl
FROM
  dtb_authority_role
WHERE
  authority_id IS NOT NULL
ORDER BY
  id ASC
