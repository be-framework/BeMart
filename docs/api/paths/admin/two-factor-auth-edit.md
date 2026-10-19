<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/two-factor-auth-edit
EC-CUBE 管理者2段階認証設定 — Setting/System Tier-2.

Admin-authenticated variant of the top-level 2FA setup renderer. The
underlying TOTP verification is not an ALPS transition in this repo,
so the resource serves the GET page and form body only.




## GET
ALPS `goAdminTwoFactorAuthEdit` に対応する GET 操作。

**ALPS**: `goAdminTwoFactorAuthEdit`



### Request

_No parameters required_

### Response

[Object: GET /admin/two-factor-auth-edit response](../schemas/get-admin-two-factor-auth-edit.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| form | object|array|null | 入力フォーム - Aura/WebForm由来のフォームオブジェクト。フレームワーク内部構造のためschemaでは存在と型のみを契約する。 | Optional | {"$comment":"Aura/WebForm\u7531\u6765\u306e\u4e0d\u900f\u660e\u30d5\u30a9\u30fc\u30e0\u8868\u73fe\u3002Resource\u5883\u754c\u3067\u306f\u30d5\u30a9\u30fc\u30e0\u306e\u5b58\u5728\u3068\u30b3\u30f3\u30c6\u30ad\u30b9\u30c8\u3060\u3051\u3092\u5951\u7d04\u3057\u3001\u5185\u90e8\u69cb\u9020\u306f\u30d5\u30ec\u30fc\u30e0\u30ef\u30fc\u30af\u5883\u754c\u306b\u59d4\u306d\u308b\u305f\u3081\u8ffd\u52a0\u30ad\u30fc\u5236\u7d04\u3092\u7f6e\u304b\u306a\u3044\u3002"} |  |
| memberName | string|null | 管理者名 - 管理者メンバーの表示名 | Required | {"minLength":0,"maxLength":255} |  |
| shopName | string|null | ショップ名 - ショップの表示名。フロント画面のヘッダやメールに表示 Fake観察文字長 12〜12; 観察値 'EC-CUBE SHOP'。 | Required | {"minLength":0,"maxLength":32} | EC-CUBE SHOP |
| authKey | string|null | 二要素認証キー - /admin/two-factor-auth-edit のレスポンスで扱う二要素認証キー。数値演算対象ではなく、照合・URL・配送追跡などに使う不透明な文字列識別子。 | Optional | {"minLength":0,"maxLength":128,"$comment":"\u30ad\u30fc/\u8ffd\u8de1\u756a\u53f7\u306f\u7167\u5408\u7528\u306e\u4e0d\u900f\u660e\u6587\u5b57\u5217\u3067\u3001\u6570\u5024\u6f14\u7b97\u5bfe\u8c61\u3067\u306f\u306a\u3044\u3002"} |  |
