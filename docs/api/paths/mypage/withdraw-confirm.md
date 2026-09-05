<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /mypage/withdraw-confirm
EC-CUBE goMypageWithdrawConfirm — 退会手続き(実行確認)
(Phase 3 — thin pure renderer).

NEW RESOURCE — flagged as a follow-up. EC-CUBE's withdraw flow has
TWO confirmation screens served by the same `mypage_withdraw`
controller action:
  1. `Mypage/withdraw.twig`         — the "退会手続きの前にご確認
     ください" warning page; its button POSTs `mode=confirm`.
     BeMart's {@see \Withdraw}::onGet + `Page/Mypage/Withdraw.html.twig`
     already port this screen.
  2. `Mypage/withdraw_confirm.twig` — rendered after `mode=confirm`;
     the "退会手続きを実行してもよろしいでしょうか？" final
     confirmation, with a cancel link + an execute button that POSTs
     `mode=complete` to actually withdraw.

BeMart's {@see \Withdraw}::onPost performs the withdrawal directly (the
Be Final converges the side-effects); the ALPS surface collapses the
two-screen confirm into the single `doWithdrawCustomer` transition, so
no `MypageWithdrawConfirm` SCREEN resource ever existed. Phase 3 needs
a page to render `Mypage/withdraw_confirm.twig` against, so this THIN
PURE RENDERER is added: no Be Framework, no domain logic, no Reasons.

This is a CONFIRM screen — it renders only a CSRF hidden token and a
submit button, no editable `<input>` fields, so (per
var/templates/README.md) no AbstractForm is needed; the form-page
recipe's `<Name>Form` exists for screens that render `<input>` fields.
The submit target is doWithdrawCustomer (`page://self/mypage/withdraw`,
POST). `csrfToken` stays null — the EventListener mirrors the live
Symfony token into the body for the subsequent POST.

The Mypage navi welcome line reads `name01`/`name02` from the page
body, which are absent here (the customer name is a MISSING BODY
FIELD follow-up — the thin renderer has no session-bound customer
context) so the navi welcome renders the empty name.

Maps to `page://self/mypage/withdraw-confirm`.




## GET
ALPS `goMypageWithdrawConfirm` に対応する GET 操作。

**ALPS**: `goMypageWithdrawConfirm`



### Request

_No parameters required_

### Response

[Object: GET /mypage/withdraw-confirm response](../schemas/get-mypage-withdraw-confirm.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| fields | array|null | 静的表示フィールド - /mypage/withdraw-confirm でテンプレートへ渡す表示用フィールド集合。フォーム入力値ではなく画面文脈データ。 | Optional | {"items":{"type":"string","title":"\u8868\u793a\u30d5\u30a3\u30fc\u30eb\u30c9","minLength":0,"maxLength":255,"description":"/mypage/withdraw-confirm \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u4f1a\u54e1\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `fields` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002"}} |  |
| submitTo | object|null | フォーム送信先リンク - /mypage/withdraw-confirm のフォーム送信に使う送信先リンク。HTTPメソッドと遷移先をまとめ、unsafe操作の入口を明示する。 | Optional | {"properties":{"href":{"title":"\u30ea\u30f3\u30afURI\u53c2\u7167\uff08URI\u53c2\u7167\uff09","description":"\u30da\u30fc\u30b8\u306eURL\u30d1\u30b9\uff08Symfony\u30eb\u30fc\u30c8\u540d\u3002\u4f8b: homepage, product_list\uff09","type":"string","format":"uri-reference","minLength":1,"maxLength":2048,"example":"/products"},"method":{"type":["string","null"],"enum":["get","post","put","patch","delete","GET","POST","PUT","PATCH","DELETE"],"title":"HTTP\u30e1\u30bd\u30c3\u30c9","description":"/mypage/withdraw-confirm \u306e\u30ea\u30f3\u30af\u307e\u305f\u306f\u30d5\u30a9\u30fc\u30e0\u9001\u4fe1\u3067\u4f7f\u3046HTTP\u30e1\u30bd\u30c3\u30c9\u3002GET/POST\u7b49\u306e\u9077\u79fb\u65b9\u6cd5\u3092\u8868\u3059\u3002"}},"additionalProperties":false,"required":["href","method"]} |  |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| csrfToken | string|null | 処理識別子 - フォーム送信の偽造を防ぐために送信元画面で発行されるトークン。Fake環境では deterministic な値を使う。 | Optional | {"minLength":8,"maxLength":160,"pattern":"^[A-Za-z0-9_.:-]+$"} | fake-csrf-token-bemart-2026 |

#### Links

| Relation | URL |
|----------|-----|
| doWithdrawCustomer | [<code>page://self/mypage/withdraw</code>](/mypage/withdraw.md) |
| goMypage | [<code>page://self/mypage</code>](/mypage.md) |