<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/favorite-list
EC-CUBE goFavoriteList — お気に入り一覧 (read pair for Pilot 13's
doAddFavorite + doRemoveFavorite).

Safe read. No CSRF (read-only). AUTHN is enforced in the Be layer:
the customer can only see their own favorites — the customerId
comes from CustomerSession, never the request body (Pilot 5 F-2
lesson).

Failure mapping:
  - SemanticVariableException  → 400 (defensive — the Input is 0-arg)
  - UnauthenticatedException   → 401 (no session)




## GET


### Request

_No parameters required_

### Response

_Not available_