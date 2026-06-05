---
layout: default
title: "/admin/block/block"
---

{% raw %}
<a href="../index.html" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/block/block
EC-CUBE doUpdateBlock + doDeleteBlock — single-row endpoint (Wave 9).

ALPS has no goBlock — the admin edits a block from the list view
directly. Only PUT and DELETE are exposed here for the domain.

Phase 3 — HTML FORM page. `onGet` exposes an {@see \AdminBlockForm}
(Ray.WebFormModule AbstractForm) as `body['form']` so the admin block
edit page (`Content/block_edit.twig` port) can render real `<input>`s
via `{{ form.input(...) }}`.

NOTE — single-row prefill: ALPS / the Be domain expose no
`GetAdminBlockInput` / `AdminBlockFetched` (single-row fetch), so
`onGet` renders the NEW-block form (the `admin_content_block_new`
case). Pre-filling an existing row would need a Be fetch Input — a
`be/src/` change out of this Phase 3 HTML wave's scope. FLAGGED:
follow-up to add `GetAdminBlockInput` for existing-block edit prefill.




## GET
Renders the block edit form (new-block case).

The JSON contexts (`app`, `prod`, `test`) ignore `body['form']`.



### Request

_No parameters required_

### Response

_Not available_
## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| blockId | string | ブロックID |  | Required |  |  |
| blockName | string | ブロック名 |  | Optional |  |  |
| blockFileName | string | ブロックファイル名 |  | Optional |  |  |


### Response

_Not available_
## DELETE


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| blockId | string | ブロックID |  | Required |  |  |


### Response

_Not available_
{% endraw %}
