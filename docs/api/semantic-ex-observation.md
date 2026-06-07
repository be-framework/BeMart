# Semantic-Ex JSON Schema Observation

この文書は `alps.json` の意味、`be/var/fake` の観察、Resource境界のschema形状から導いたJSON Schema制約の根拠です。

## Quality Baseline / Gate

- Response schema files: 234
- Request schema files: 154
- Broad escape union occurrences: 0
- additionalProperties=true occurrences: 0
- Scalar properties without semantic constraints: 0
- Suspicious descriptions: 0
- Generic fallback descriptions: 0
- Mechanical titles: 0
- Wide transport unions: 0
- Mixed boundary IDs/usages: 53 (approved: 53, unapproved: 0)
- Open collection item objects (monitor): 0
- Opaque form objects (monitor): 61
- Dynamic rows accepted (monitor): 17
- Shapeable opaque form objects: 0
- Opaque form objects without reason: 0
- Dynamic rows without reason: 0
- Dynamic rows with no properties and no exception: 0
- String-token mixed IDs: 0
- DB-ID mixed without transport reason: 0
- ALPS未直結property（分類済み）: 1347
- ALPS未直結property（未分類）: 0

必須ゼロ品質ゲート: broad escape union / additionalProperties=true / scalarWithoutConstraints / suspiciousDescriptions / wideTransportUnions / unclassified ALPS gap / genericFallbackDescriptions / mechanicalTitles / unapprovedMixedBoundaryIds / shapeableOpaqueFormObjects / dynamicRowsWithoutReason / stringTokenMixedIds / dbIdMixedWithoutTransportReason。

## Verification Boundary
このschema資産はPHP/ALPS/phpunit差分から隔離して管理します。Runtime検査とApiDoc 234/154/234の統合検証には、別WIPで導入済みのResource `#[JsonSchema]`/`#[Alps]` 属性と `JsonSchemaModule` のインストールが必要です。
clean schema worktree単独ではPHP属性を持たないため、schema品質ゲートを直接検証し、統合テストは既存PHP WIPへschema資産だけを重ねた一時検証コピーで確認します。

## Core Semantic Constraints

### productCode — 商品コード
- ALPS meaning: SKU/品番。在庫管理や受注明細での識別に使用
- Fake observation: Fake観察文字長 10〜26。
- Types: {'string': 112}; sources: query/product_class_get.jsonl, product_classes.json, query/product_get.jsonl, query/product_search.jsonl, query/product_export.jsonl
- Schema decision: see `$defs` embedded in each generated schema; constraints are intentionally repeated so each schema is self-contained for BEAR.ApiDoc and runtime validation.

### email — メールアドレス
- ALPS meaning: 会員のログインIDを兼ねる。有効会員間で一意
- Fake observation: Fake観察文字長 15〜58; 観察値 'bob@example.com', 'login-test@example.com', 'alice@example.com', 'carol@example.com', 'provisional@example.com', 'withdrawn-30000000aaaa3333bbbb4444cccc5555@example.invalid'。
- Types: {'string': 38}; sources: query/customer_search.jsonl, query/customer_find_by_email.jsonl, query/customer_find_by_id.jsonl, customers.json, query/customer_email_exists.jsonl
- Schema decision: see `$defs` embedded in each generated schema; constraints are intentionally repeated so each schema is self-contained for BEAR.ApiDoc and runtime validation.

### postalCode — 郵便番号
- ALPS meaning: 日本の郵便番号。ハイフンなし7桁またはハイフン付き8桁
- Fake observation: Fake観察文字長 7〜8; 観察値 '1500001', '1000005', '5300001', '530-0001'; null 18/33。
- Types: {'string': 15, 'null': 18}; sources: query/customer_search.jsonl, query/customer_find_by_id.jsonl, customers.json, query/customer_find_by_email.jsonl, base_info.json
- Schema decision: see `$defs` embedded in each generated schema; constraints are intentionally repeated so each schema is self-contained for BEAR.ApiDoc and runtime validation.

### phoneNumber — 電話番号
- ALPS meaning: 日本の電話番号形式（ハイフン区切り）
- Fake observation: Fake観察文字長 10〜10; 観察値 '0312345678', '0901234567', '0612345678'; null 18/33。
- Types: {'string': 15, 'null': 18}; sources: query/customer_search.jsonl, query/customer_find_by_id.jsonl, customers.json, query/customer_find_by_email.jsonl, base_info.json
- Schema decision: see `$defs` embedded in each generated schema; constraints are intentionally repeated so each schema is self-contained for BEAR.ApiDoc and runtime validation.

### pref — 都道府県
- ALPS meaning: 日本の都道府県（1=北海道〜47=沖縄県）。住所の最上位区分として顧客・受注・配送先で使用。配送料の地域区分（DeliveryFee）や税率の地域設定（TaxRule）にも使用
- Fake observation: Fake観察数値 13〜27; 観察値 '13', '27'; null 3/9。
- Types: {'integer': 6, 'null': 3}; sources: customers.json, base_info.json, query/tbase_info_get.json, query/address_get.jsonl, query/address_list_by_customer.jsonl
- Schema decision: see `$defs` embedded in each generated schema; constraints are intentionally repeated so each schema is self-contained for BEAR.ApiDoc and runtime validation.

### price02 — 販売価格
- ALPS meaning: 実際の販売価格（税抜）。税計算・小計計算のベース
- Fake observation: Fake観察数値 800〜28000。
- Types: {'integer': 70}; sources: product_classes.json, query/product_class_get.jsonl, query/product_search.jsonl, query/product_export.jsonl, products.json
- Schema decision: see `$defs` embedded in each generated schema; constraints are intentionally repeated so each schema is self-contained for BEAR.ApiDoc and runtime validation.

### quantity — 数量
- ALPS meaning: 購入数量。カート明細と受注明細で共通使用
- Fake observation: Fake観察数値 1〜3; 観察値 '1', '2', '3'。
- Types: {'integer': 14}; sources: orders.json, finalized_orders.json, query/order_items_by_order_no.jsonl
- Schema decision: see `$defs` embedded in each generated schema; constraints are intentionally repeated so each schema is self-contained for BEAR.ApiDoc and runtime validation.

### productStatus — 商品ステータス
- ALPS meaning: 1=公開（フロント表示）, 2=非公開（フロント非表示）, 3=廃止（論理削除、管理画面でもデフォルト非表示）
- Fake observation: Fake観察数値 1〜3; 観察値 '1', '2', '3'。
- Types: {'integer': 7}; sources: products.json
- Schema decision: see `$defs` embedded in each generated schema; constraints are intentionally repeated so each schema is self-contained for BEAR.ApiDoc and runtime validation.

### customerStatus — 会員ステータス
- ALPS meaning: 1=仮会員（メール未認証）, 2=本会員（認証済み）, 3=退会。退会時はメールアドレスが無効化される
- Fake observation: Fake観察数値 1〜2; 観察値 '2', '1'。
- Types: {'integer': 5}; sources: customers.json
- Schema decision: see `$defs` embedded in each generated schema; constraints are intentionally repeated so each schema is self-contained for BEAR.ApiDoc and runtime validation.

### orderStatus — 受注ステータス
- ALPS meaning: 1=新規受付, 3=注文取消, 4=対応中, 5=発送済み, 6=入金済み, 7=決済処理中, 8=購入処理中, 9=返品。Symfony Workflowステートマシンで遷移を制御。許可される遷移: pay(1->6), packing(1,6->4), cancel(1,4,6->3), back_to_in_progress(3->4), ship(1,6,4->5), return(5->9), cancel_return(9->5)。7と8はPurchaseFlow内で直接セットされステートマシン遷移の対象外
- Fake observation: Fake観察数値 1〜1; 観察値 '1'。
- Types: {'integer': 1}; sources: finalized_orders.json
- Schema decision: see `$defs` embedded in each generated schema; constraints are intentionally repeated so each schema is self-contained for BEAR.ApiDoc and runtime validation.

### cartKey — カートキー
- ALPS meaning: カート分離キー。形式: {セッションプレフィックス}_{販売種別ID}。EC-CUBEは販売種別ごとにカートを分離するため、異なる販売種別の商品は別カートになる
- Fake observation: Fake観察文字長 18〜23; 観察値 'session-prefix-1_1', 'session-prefix-1_2', 'session-checkout-pilot5'。
- Types: {'string': 12}; sources: query/cart_by_key.jsonl, carts.json, query/cart_by_session_prefix.jsonl
- Schema decision: see `$defs` embedded in each generated schema; constraints are intentionally repeated so each schema is self-contained for BEAR.ApiDoc and runtime validation.

## Exceptions

- `form`, framework form objects: object with documented `additionalProperties: true`; internal Aura/WebForm state is not an application contract.
- Dynamic option maps such as `productStatusOptions`: object maps with constrained value type where the numeric keys would break ApiDoc term rendering if expressed as literal property names.
- `csv` and `pdf`: JSON boundary validates transport shape/size; CSV/PDF internal structure belongs to compatibility services.
- Collections whose Resource schema currently exposes no item properties are marked as object arrays with a `$comment`; these are explicit TODOs for future Fake expansion, not silent pass-throughs.

## ALPS未直結propertyの分類
ALPS descriptor名と完全一致しないpropertyは、無視せず次の例外クラスへ分類して監査対象にしています。未分類が0であることを品質ゲートにします。
- hypermedia: 858
- presentation: 98
- collection-or-row: 81
- identifier: 67
- counter: 64
- runtime-flag: 57
- pagination: 57
- transport-payload: 25
- domain-derived: 23
- form-context: 15
- operation-result: 2
