---
layout: default
title: "/admin/page/page"
---

{% raw %}
<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/page/page
EC-CUBE goPage + doUpdatePage + doDeletePage — single-row endpoint
(Wave 9 CMS).

Phase 3 — HTML FORM page. `onGet` exposes an {@see \AdminPageForm}
(Ray.WebFormModule AbstractForm) as `body['form']` pre-filled with the
persisted row, so the admin page editor (`Content/page_edit.twig`
port) can render real `<input>`s via `{{ form.input(...) }}`. The JSON
contexts (`app`, `prod`, `test`) ignore `body['form']`.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pageId | string | ページID |  | Optional |  |  |


### Response

_Not available_
## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pageId | string | ページID |  | Required |  |  |
| pageName | string | ページ名 |  | Optional |  |  |
| pageUrl | string | ページURL |  | Optional |  |  |
| pageFileName | string | テンプレートファイル名 |  | Optional |  |  |


### Response

_Not available_
## DELETE


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| pageId | string | ページID |  | Required |  |  |


### Response

_Not available_
{% endraw %}
