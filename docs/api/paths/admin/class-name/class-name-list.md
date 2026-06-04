---
layout: default
title: "/admin/class-name/class-name-list"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/class-name/class-name-list
EC-CUBE goClassNameList + doCreateClassName — collection endpoint
(Wave 7).

- GET  → goClassNameList   (admin lists axes — safe read)
  - POST → doCreateClassName (admin adds a new axis)

Single-row affordances (`doUpdateClassName`, `doDeleteClassName`)
live at `page://self/admin/class-name/class-name`. There is no
dedicated `goClassName` (admin reads the list directly).




## GET


### Request

_No parameters required_

### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| classNameLabel | string | 規格名 |  | Required |  |  |


### Response

_Not available_