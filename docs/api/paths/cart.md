<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /cart
EC-CUBE goCart — current shopping session の cart 一覧 (Pilot 9).

Safe read. No CSRF, no AUTHZ — the cart is bound to the
sessionPrefix cookie, ownership is implicit. Returns 200 with a
(possibly empty) list of carts plus per-session totals.




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| sessionPrefix | string |  | session-prefix-1 | Optional |  |  |


### Response

_Not available_