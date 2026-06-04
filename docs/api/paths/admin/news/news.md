{% raw %}
<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/news/news
EC-CUBE goNews + doUpdateNews + doDeleteNews — single-row endpoint
(Wave 9).

Phase 3 — HTML FORM page (admin pilot). `onGet` exposes an
{@see \AdminNewsForm} (Ray.WebFormModule AbstractForm) as `body['form']`
pre-filled with the persisted row so the admin edit page can render
real `<input>`s via `{{ form.input(...) }}`. The form is a
field-definition + renderer only — VALIDATION AUTHORITY STAYS WITH the
Be Framework Becoming chain. The JSON contexts (`app`, `prod`, `test`)
ignore `body['form']`; the resource tests assert key-wise on `body`
and are unaffected. FormFactory is self-sufficient (no Ray.Di bindings
needed).




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| newsId | string | ニュースID |  | Optional |  |  |


### Response

_Not available_
## PUT


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| newsId | string | ニュースID |  | Required |  |  |
| newsTitle | string | ニュースタイトル |  | Optional |  |  |
| newsDescription | string | ニュース本文 |  | Optional |  |  |
| newsUrl | string | 外部URL |  | Optional |  |  |
| publishDate | string | 公開日 |  | Optional |  |  |
| linkMethod | bool | 新規ウィンドウで開く |  | Optional |  |  |


### Response

_Not available_
## DELETE


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| newsId | string | ニュースID |  | Required |  |  |


### Response

_Not available_
{% endraw %}
