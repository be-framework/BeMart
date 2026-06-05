---
layout: default
title: "/admin/category/edit"
---

<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/category/edit
EC-CUBE カテゴリ登録 / カテゴリ編集 — Product Tier-2
(`admin/Product/category.twig`, the category tree-list + inline
add/edit screen).

GET /admin/category/edit                 → tree list + blank "new" form
  GET /admin/category/edit?categoryId=…    → tree list + form pre-filled

Thin GET renderer. The sibling JSON resources
{@see \MyVendor\BeMart\Resource\Page\Admin\Category\CategoryList}
(collection + create) and {@see \MyVendor\BeMart\Resource\Page\Admin\Category\Category}
(update / delete) carry the writes; this resource is the HTML editor
shell — it renders the category tree alongside the add/edit form. An
empty `$categoryId` renders the blank "新規カテゴリ" form (the
render-smoke test exercises this with empty JSON-backed fake storage); a known
categoryId pre-fills; an unknown categoryId is 404.

AUTHZ delegates to the Be Finals, which raise
{@see \UnauthorizedAdminAccessException} for a non-admin firewall —
surfaced as 403.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| categoryId | string | カテゴリID |  | Optional |  |  |


### Response

_Not available_