<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/mail-template
EC-CUBE doUpdateMailTemplate + goMailTemplateList — メールテンプレート
(Wave 8 + Wave 9).

- GET  → goMailTemplateList (collection list, safe, admin, Wave 9ι)
  - POST → doUpdateMailTemplate (per-id update, idempotent, Wave 8ε)

The migration scope only covers UPDATE of the subject — creating a
new template requires setting the underlying file_name, which is
Phase 2 scope. 厳密移植 alignment: dtb_mail_template has no body
columns (mail bodies are on-disk Twig files), so the former
mailBody / mailHtmlBody inputs were dropped.

Failure mapping:
  - Invalid CSRF                          → 403 (POST only)
  - SemanticVariableException             → 400 (subject format)
  - UnauthorizedAdminAccessException      → 403 (no admin session)
  - MailTemplateNotFoundException         → 404 (unknown id)




## GET
Wave 9ι: goMailTemplateList — admin lists every mail template.



### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| mailTemplateId | int | メールテンプレートID |  | Required |  |  |
| mailSubject | string | メール件名 |  | Required |  |  |


### Response

_Not available_