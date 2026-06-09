SELECT COUNT(*) INTO @bemart_mail_template_found
FROM dtb_mail_template
WHERE id = CAST(JSON_VALUE(CAST(:entity AS CHAR), '$.mailTemplateId') AS UNSIGNED);
UPDATE dtb_mail_template
SET mail_subject = JSON_VALUE(CAST(:entity AS CHAR), '$.subject'), update_date = NOW()
WHERE id = CAST(JSON_VALUE(CAST(:entity AS CHAR), '$.mailTemplateId') AS UNSIGNED);
SELECT CASE WHEN @bemart_mail_template_found > 0 THEN 1 ELSE 0 END AS updated
