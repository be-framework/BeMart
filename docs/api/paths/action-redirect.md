<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /action-redirect
Safe HTML endpoint for legacy storefront links whose state transition is
performed by JavaScript or by a POST-only route in EC-CUBE.

It never renders a placeholder page. The browser is redirected to a stable
page so link crawls do not surface "not implemented" copy while templates
are migrated to explicit POST forms.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| returnTo | string |  |  | Optional |  |  |


### Response

_Not available_
## POST


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| returnTo | string |  |  | Optional |  |  |


### Response

_Not available_