<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/mail-template/create
Creates a disposable admin mail template row through the Web/HTTP boundary.

EC-CUBE stores mail bodies on disk; this Resource only creates the database
master row needed for the admin subject/update/delete workflow.




## POST
Creates an admin mail template database row for workflow verification.

**ALPS**: `doCreateMailTemplate`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| mailTemplateName | string | Admin-visible mail template name. |  | Required | {"minLength":1,"maxLength":255} |  |
| fileName | string | Twig template file path stored in dtb_mail_template.file_name. |  | Required | {"minLength":1,"maxLength":255} |  |
| mailSubject | string | Mail subject stored with the template row. |  | Required | {"minLength":1,"maxLength":255} |  |


### Response

[Object: POST /admin/mail-template/create response](../schemas/post-admin-mail-template-create.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| mailTemplateId | int | メールテンプレートID | Required | {"minimum":1,"maximum":2147483647} |  |
| mailTemplateName | string | テンプレート名 | Required | {"minLength":1,"maxLength":255} |  |
| fileName | string | ファイル名 | Required | {"minLength":1,"maxLength":255} |  |
| mailSubject | string | メール件名 | Required | {"minLength":0,"maxLength":255} |  |

#### Links

| Relation | URL |
|----------|-----|
| goMailTemplateList | [<code>page://self/admin/mail-template</code>](/admin/mail-template.md) |
| doUpdateMailTemplate | [<code>page://self/admin/mail-template</code>](/admin/mail-template.md) |
| doDeleteMailTemplate | [<code>page://self/admin/mail-template</code>](/admin/mail-template.md) |