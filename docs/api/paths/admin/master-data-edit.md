<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /admin/master-data-edit
EC-CUBE マスタデータ編集 — Setting/System (doUpdateMasterData).

Separate resource from {@see \MasterData} (which owns GET + the
`doSelectMasterData` PUT on the same `/admin_setting_system_masterdata`
URL) so the edit verb does not collide. `onPut` drives the Be
`doUpdateMasterData` transition; the destructive bulk write is isolated
behind {@see \MyVendor\BeMart\Be\Reason\Service\MasterDataWriterInterface}.




## PUT
ALPS `doUpdateMasterData` に対応する PUT 操作。

**ALPS**: `doUpdateMasterData`



### Request

| Name | Type | Description | Default | Required | Constraints | Example |
|------|------|-------------|---------|----------|-------------|---------|
| masterType | string | マスタ種別（入力） - /admin/master-data-edit の処理文脈から派生したマスタ種別。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 |  | Required | {"minLength":0,"maxLength":255,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |
| rows | array | 行データ - /admin/master-data-edit のマスタ/CSV行データ。列集合は対象マスタにより変わるため、既知列を優先して契約する。 | array () | Optional | {"items":{"type":"object","title":"\u884c\uff08\u5165\u529b\uff09","description":"/admin/master-data-edit \u306e\u30ec\u30b9\u30dd\u30f3\u30b9\u306b\u542b\u307e\u308c\u308b\u884c\u3002\u89aa\u30b3\u30ec\u30af\u30b7\u30e7\u30f3 `rows` \u306e1\u884c\u3092\u8868\u3057\u3001\u56fa\u5b9a\u3067\u304d\u308b\u696d\u52d9\u5217\u306fschema property\u3067\u660e\u793a\u3059\u308b\u3002","properties":{"name":{"type":["string","null"],"minLength":0,"maxLength":32,"title":"\u51e6\u7406\u8868\u793a\u540d\uff08\u5165\u529b\uff09","description":"Fake\u89b3\u5bdf\u6587\u5b57\u9577 1\u301c7; \u89b3\u5bdf\u5024 '\u30c6\u30b9\u30c8\u7ba1\u7406\u8005', '\u526f\u7ba1\u7406\u8005', '\u5e97\u8217\u30aa\u30fc\u30ca\u30fc', '\u524a\u9664\u6e08\u307f\u7ba1\u7406\u8005', 'Red', 'Blue', 'S', 'Color'\u3002","example":"\u30c6\u30b9\u30c8\u7ba1\u7406\u8005","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"value":{"type":["string","null"],"minLength":0,"maxLength":255,"title":"\u30de\u30b9\u30bf\u5024","description":"/admin/master-data-edit \u306e\u30de\u30b9\u30bf\u7a2e\u5225\u307e\u305f\u306f\u30de\u30b9\u30bf\u884c\u306b\u8868\u793a\u3055\u308c\u308b\u5024\u3002\u9078\u629e\u80a2\u306e\u8868\u793a/\u4fdd\u5b58\u5358\u4f4d\u3068\u3057\u3066\u6271\u3046\u3002","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"sortNo":{"type":["integer","null"],"title":"\u8868\u793a\u9806\uff08\u5165\u529b\uff09","description":"\u4e00\u89a7\u306b\u304a\u3051\u308b\u4e26\u3073\u9806 Fake\u89b3\u5bdf\u6570\u5024 1\u301c20; \u89b3\u5bdf\u5024 '1', '3', '2', '4', '10', '20'\u3002","example":1,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"enabled":{"type":["boolean","null"],"title":"\u51e6\u7406\u72b6\u614b\u30d5\u30e9\u30b0\uff08\u5165\u529b\uff09","description":"\u89b3\u5bdf\u5024 'true', 'false'\u3002","example":"true","$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."}},"$comment":"\u914d\u5217\u8981\u7d20\u306f\u30de\u30b9\u30bf/CSV/option map\u306e\u52d5\u7684\u884c\u3067\u3042\u308a\u3001\u5217\u96c6\u5408\u304c\u5bfe\u8c61\u7a2e\u5225\u306b\u3088\u308a\u5909\u308f\u308b\u305f\u3081\u56fa\u5b9ashape\u5316\u3057\u306a\u3044\u3002\u65e2\u77e5property\u306f\u5951\u7d04\u3057\u3001\u8ffd\u52a0\u5217\u306f\u8a72\u5f53\u30b5\u30fc\u30d3\u30b9\u5883\u754c\u3067\u6271\u3046\u3002 Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."},"minItems":0,"$comment":"Request schema is transport-level; business invalid values are allowed through to Resource/Semantic validation."} |  |


### Response

[Object: PUT /admin/master-data-edit response](../schemas/put-admin-master-data-edit.json)

| Name | Type | Description | Required | Constraints | Example |
|------|------|-------------|----------|-------------|---------|
| message | string|null | マスタデータメッセージ - /admin/master-data-edit のレスポンスに含まれる処理結果メッセージ。注文時お問い合わせ欄ではなく、画面遷移や完了表示のための通知文。 | Optional | {"minLength":0,"maxLength":32} | 配送は平日希望です。 |
| transitionId | string | ALPS遷移ID - このレスポンス/操作が対応するALPS遷移ID。クライアントの状態遷移追跡に使う。 | Required | {"minLength":2,"maxLength":96,"pattern":"^(go|do)[A-Z][A-Za-z0-9]*$"} | doAddCartItem |
| masterType | string | マスタ種別 - /admin/master-data-edit の処理文脈から派生したマスタ種別。ALPS基礎語だけでは単位や用途が不足するため、このResource上の意味を明示する。 | Required | {"minLength":1,"maxLength":255} |  |
| count | int|null | 件数 - /admin/master-data-edit のレスポンスで返す件数。一覧・集計・処理結果の規模を表す非負整数。 | Required | {"minimum":0,"maximum":2147483647} |  |

#### Links

| Relation | URL |
|----------|-----|
| goMasterData | [<code>page://self/admin/master-data</code>](/admin/master-data.md) |