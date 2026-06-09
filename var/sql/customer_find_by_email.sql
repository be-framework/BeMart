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
  email = :email
LIMIT
  1
