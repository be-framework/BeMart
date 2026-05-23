SELECT id, email, password, name01, name02, kana01, kana02, company_name, phone_number, postal_code, pref_id, addr01, addr02, birth, sex_id, job_id, customer_status_id, secret_key
FROM dtb_customer
WHERE (:nameKeyword IS NULL OR :nameKeyword = '' OR INSTR(CONCAT_WS(' ', name01, name02, company_name), :nameKeyword) > 0)
  AND (:emailKeyword IS NULL OR :emailKeyword = '' OR INSTR(email, :emailKeyword) > 0)
ORDER BY id ASC
LIMIT :limit
