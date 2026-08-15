<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/withdraw-complete
EC-CUBE goMypageWithdrawComplete — 退会手続き(完了)
(Phase 3 — thin pure renderer).

NEW RESOURCE — flagged as a follow-up. EC-CUBE lands on
`Mypage/withdraw_complete.twig` after a successful `doWithdrawCustomer`
(the EventListener clears the session, then the controller renders the
complete screen). BeMart's {@see \Withdraw}::onPost converges the
withdrawal side-effects and returns the `CustomerWithdrawn` projection;
the ALPS surface declares the single transition `goTop` — no
`MypageWithdrawComplete` SCREEN resource ever existed. Phase 3 needs a
page to render `Mypage/withdraw_complete.twig` against, so this THIN
PURE RENDERER is added: no Be Framework, no domain logic, no Reasons.

`Mypage/withdraw_complete.twig` is a static confirmation (the
withdrawal-complete message + a go-to-top button). Unlike the other
Mypage screens it does NOT include the account navi (the customer is
already withdrawn / logged out), so the port carries no navi and no
customer-name context. It reads no dynamic data, so the thin-renderer
body carries nothing to surface.

Maps to `page://self/mypage/withdraw-complete`.




## GET
ALPS `goMypageWithdrawComplete` に対応する GET 操作。

**ALPS**: `goMypageWithdrawComplete` - 退会完了を見る



### Request

_No parameters required_

### Response

[Object: GET /mypage/withdraw-complete response](../schemas/get-mypage-withdraw-complete.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| fields | array|null | 静的表示フィールド - /mypage/withdraw-complete でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/mypage/withdraw-complete \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u4f1a\u54e1\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | string|null | フォーム送信先リンク - /mypage/withdraw-complete のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"minLength":0,"maxLength":255} |  |
| staticContent | object|null | 静的コンテンツ - /mypage/withdraw-complete で表示する規約・ヘルプ・エラー等の静的ページ本文とセクション情報。 | Optional | {"properties":{"title":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u51e6\u7406\u30bf\u30a4\u30c8\u30eb","description":"/mypage/withdraw-complete \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u3067\u8868\u793a\u307e\u305f\u306f\u30c6\u30f3\u30d7\u30ec\u30fc\u30c8\u63cf\u753b\u306b\u4f7f\u3046\u51e6\u7406\u30bf\u30a4\u30c8\u30eb\u3002\u540c\u540dproperty\u3067\u3082\u89aa\u6587\u8108 `root` \u306b\u3088\u3063\u3066\u610f\u5473\u3092\u5206\u3051\u308b\u3002"},"page":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30da\u30fc\u30b8\u8b58\u5225\u5b50","description":"/mypage/withdraw-complete \u306e\u9759\u7684\u30b3\u30f3\u30c6\u30f3\u30c4\u3067\u8868\u793a\u5bfe\u8c61\u30da\u30fc\u30b8\u3092\u8b58\u5225\u3059\u308b\u5024\u3002\u30da\u30fc\u30b8\u756a\u53f7\u3067\u306f\u306a\u3044\u3002"}},"additionalProperties":false,"required":["title","page"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |

#### Links

| Relation | URL |
|----------|-----|
| goTop | [<code>page://self/</code>](/.md) |