---
layout: default
title: "/admin/page/page-list"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/page/page-list
EC-CUBE goPageList + doCreatePage — collection endpoint (Wave 9 CMS).

- GET  → goPageList    (admin lists CMS pages — safe read)
- POST → doCreatePage  (admin creates a new free page)




## GET


### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pageName | string | ページ名 |  | Required |  |  |
| pageUrl | string | ページURL |  | Required |  |  |
| pageFileName | string | テンプレートファイル名 |  | Required |  |  |


### Response

_Not available_