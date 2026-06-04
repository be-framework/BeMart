---
layout: default
title: "/admin/template/template-add"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/template/template-add
EC-CUBE テンプレート登録 — Store Tier-2 (`admin/Store/template_add.twig`).

GET /admin/store/template/add → template-upload screen

Thin GET renderer for EC-CUBE's design-template registration screen:
a template code, a template name and a zip-archive file-upload form.
The matching `doTemplateInstall` write transition is a Phase-A stub —
this port renders the upload shell only, mirroring the Product
CSV-upload Tier-2 wave ({@see \MyVendor\BeMart\Support\Resource\AbstractCsvUpload}).

AUTHZ is a direct admin-session check (Pattern B — no Be transition is
invoked on the GET path; an anonymous admin → 403). The form renders
blank against empty JSON-backed fake storage — no storage is seeded.




## GET


### Request

_No parameters required_

### Response

_Not available_
## POST
Installs an uploaded design template (doInstallTemplate). ALPS
marks it `unsafe` → POST.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| templateCode | string | テンプレートコード |  | Required |  |  |
| templateName | string | テンプレート名 |  | Required |  |  |


### Response

_Not available_