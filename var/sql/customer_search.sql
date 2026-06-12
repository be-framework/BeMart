SELECT
  c.id,
  c.email,
  c.password,
  c.name01,
  c.name02,
  c.kana01,
  c.kana02,
  c.company_name,
  c.phone_number,
  c.postal_code,
  c.pref_id,
  c.addr01,
  c.addr02,
  c.birth,
  c.sex_id,
  c.job_id,
  c.customer_status_id,
  c.secret_key
FROM
  (
    SELECT
      id,
      email,
      password,
      name01,
      name02,
      kana01,
      kana02,
      company_name,
      phone_number,
      postal_code,
      pref_id,
      addr01,
      addr02,
      DATE_FORMAT(birth, '%Y-%m-%d') AS birth,
      sex_id,
      job_id,
      customer_status_id,
      secret_key
    FROM
      dtb_customer
    WHERE
      (
        :nameKeyword IS NULL
        OR :nameKeyword = ''
        OR INSTR(
          CONCAT_WS(' ', name01, name02, company_name),
          :nameKeyword
        ) > 0
      )
      AND (
        :emailKeyword IS NULL
        OR :emailKeyword = ''
        OR INSTR(email, :emailKeyword) > 0
      )
    ORDER BY
      id ASC
    LIMIT
      :limit
  ) c
