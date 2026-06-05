---
layout: default
title: "/admin/master-data"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/master-data
EC-CUBE マスタデータ管理 — Setting/System Tier-2.

GET renderer backed by the existing Be admin-master registry. This is
body-shape work for the generic master-data page: the resource exposes
selectable master types plus rows as `{id, name}` without inventing
values in Twig.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| masterType | string |  | tag | Optional |  |  |


### Response

_Not available_
## PUT
Selects which master to view (doSelectMasterData). ALPS marks it
`idempotent` → PUT; returns the chosen master's rows.



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| masterType | string |  | tag | Optional |  |  |


### Response

_Not available_