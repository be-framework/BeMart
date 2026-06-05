---
layout: default
title: "/admin/template/template-list"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/template/template-list
EC-CUBE goTemplateList — list-only endpoint (Wave 9). ALPS exposes
no other affordances; template upload / activation is Phase 2.






## GET


### Request

_No parameters required_

### Response

_Not available_
## PUT
Activates a template (doSelectTemplate). ALPS idempotent → PUT.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| templateId | string | テンプレートID |  | Required |  |  |


### Response

_Not available_
## DELETE
Deletes a template (doDeleteTemplate). ALPS idempotent → DELETE.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| templateId | string | テンプレートID |  | Required |  |  |


### Response

_Not available_
## POST
Downloads a template zip (doDownloadTemplate). ALPS unsafe → POST.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| templateId | string | テンプレートID |  | Required |  |  |


### Response

_Not available_