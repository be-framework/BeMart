INSERT INTO dtb_authority_role (
  authority_id, creator_id, deny_url, create_date, update_date,
  discriminator_type
)
SELECT
  jt.authority,
  CASE WHEN :creatorId REGEXP '^[0-9]+$' THEN
    CAST(:creatorId AS UNSIGNED)
  ELSE
    NULL
  END,
  jt.deny_url,
  NOW(),
  NOW(),
  'authorityrole'
FROM
  JSON_TABLE(
    :rules,
    '$[*]' COLUMNS (
      authority INT PATH '$.authority',
      deny_url VARCHAR(4000) PATH '$.denyUrl'
    )
  ) AS jt
WHERE
  jt.deny_url IS NOT NULL
  AND jt.deny_url <> ''
