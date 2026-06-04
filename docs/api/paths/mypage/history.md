<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/history
EC-CUBE goMypageHistory — 注文履歴詳細 (Mypage/History).

Safe read. No CSRF (read-only). AUTHN + AUTHZ are enforced in the
Be layer: the customer can only see their own past orders, and the
orderNo→customerId AUTHZ check is sequenced after existence so the
404 vs 403 distinction is preserved (Pilot 12 pattern).

Failure mapping:
  - SemanticVariableException         → 400 (orderNo malformed)
  - UnauthenticatedException          → 401 (no session)
  - UnauthorizedOrderAccessException  → 403 (not the order owner)
  - OrderNotFoundException            → 404 (no such order)




## GET


### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| orderNo | string | 注文番号 |  | Required |  |  |


### Response

_Not available_