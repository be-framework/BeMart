---
layout: default
title: "Hard ActionRedirect 完走 + Phase-A stub 実体化 — 引き継ぎ"
---

# Hard ActionRedirect 完走 + Phase-A stub 実体化 — 引き継ぎ

> このブランチ `claude/gallant-fermat-CpUqD`（PR #28, draft）の作業記録と、
> **ローカルで続きを実装するための引き継ぎ**。クラウド実行セッションが長くなり
> ツール出力が不安定になったため、残り2件はローカル（codex 相談可）で実装する。

## 完了済み（PR #28 に push 済み・全 green）

`docs/eccube-feature-alps-status.html` で **難易度 Hard かつ「安全退避(ActionRedirect)」** だった
**22行（18ユニークルート）を 0 件に**。各ルートを具体 Be/BEAR リソースへ接続し、Be ドメイン
遷移（Input/Semantic/Final/Reason）まで実装。副作用は Issue #24 PDF pilot 方式の
**境界サービス（`be/src/Reason/Service/*Interface` + `src/Compatibility/Eccube/` 実装 + Fake）** へ隔離。

コミット（1.x からの 7 本、系統ごと）:

1. 認証系: `doChangePassword` を具体Resourceへ接続
2. 認証系: 2FA確認/設定・セキュリティ設定（doVerifyTwoFactorAuth / doSetTwoFactorAuth / doUpdateSecurity）
3. コンテンツ系: cache/css/js/maintenance（doClearCache / doUpdateContentCss / doUpdateContentJs / doToggleMaintenance）
4. マスタデータ系: select/update（doSelectMasterData / doUpdateMasterData、後者は新規 `MasterDataEdit` へ分離）
5. 規格CSV系: 規格名/規格分類の export/import（goExportClassName / goExportClassCategory / doImportClassNameCsv / doImportClassCategoryCsv）
6. ストア/テンプレート系: select/delete/download/install（doSelectTemplate / doDeleteTemplate / doDownloadTemplate / doInstallTemplate）
7. Phase-A stub 実体化: カテゴリ/配送CSV取込（doImportCategoryCsv / doImportShippingCsv）

導入した境界サービス（`be/src/Reason/Service/` interface ＋ `src/Compatibility/Eccube/` 既定 ＋ `tests/Fake/Reason/Service/` Fake）:
`TwoFactorAuthInterface` / `SecurityConfigWriterInterface` / `CacheClearerInterface` /
`CustomizeAssetWriterInterface` / `MaintenanceModeInterface` / `MasterDataWriterInterface` /
`ClassCsvCompatibilityInterface`(+`CsvDocument`) / `TemplateCompatibilityInterface`(+`TemplateArchive`)。

Phase-A stub 実体化（commit 7）:
- `be/src/Final/CategoryCsvImported.php` — 4列(id/name/parentId/削除flag)parse → `CategoryStorageInterface::put/delete`、空IDは `CategoryIdQueryInterface::next` 採番。
- `be/src/Final/AdminShippingCsvImported.php` — 受注番号/お問い合わせ番号 parse → 注文照合 → `ShippingAddressStorageInterface::updateTrackingNumber`（`doUpdateTrackingNumber` と同 surface）。

## ⚠️ 環境セットアップ（必須・ハマりどころ）

`ray/fake-query` は pin された dev ブランチで、**素の `composer install` ではテストが起動しない**
（`FakeQueryModule` ctor シグネチャ不整合 + fixture 規約差）。リポジトリ同梱の patch を当てて解決する:

- `composer.json` の `extra.patches` で `patches/ray-fake-query-be-bemart.patch` を `cweagans/composer-patches` 経由適用。
- **ローカル**: 通常 `composer install` で patch が当たる（plugin 有効）。
- **クラウド/root 実行時のみ**: `COMPOSER_ALLOW_SUPERUSER=1 composer install` でないと plugin が無効化され patch 未適用になる。
- patch 適用確認: `grep -c fakeDirs vendor/ray/fake-query/src/FakeQueryConfig.php` が `>0`。

## 検証コマンド

```bash
# DB不要スイート（これが green なら OK）
./vendor/bin/phpunit --testsuite fake      # 直近: 1317 tests green
# 静的解析
composer psalm        # No errors（179 は既存 info 級）
composer psalm-taint  # No errors
# ステータスHTML再生成（route 張替後に必須）
php bin/generate-eccube-feature-alps-status.php
# Hard ActionRedirect 残数の確認（0 のはず）
grep -oP 'data-implementation-status="安全退避\(ActionRedirect\)" data-difficulty="Hard"' docs/eccube-feature-alps-status.html | wc -l
```

ステータス自動判定の要点: `bin/generate-eccube-feature-alps-status.php` は
`config/aura-routes.php` の resource URI が `action-redirect` を含むか否かで「安全退避/実装済み」を
判定する。**route を action-redirect → 具体URIへ張り替え + 再生成するだけで「実装済み」に変わる**。
難易度 Hard は内容ベース判定なので維持される。`tests/Docs/EccubeFeatureAlpsStatusHtmlTest.php` の
監査テストは「件数非依存（残った Hard ActionRedirect 行が既知監査セットに属するか）」へ更新済み。

## 正準パターン（残作業もこれに従う）

- **Resource→Be起動**: `src/Resource/Page/Admin/ToggleVisible.php` — `BecomingInterface` 注入 →
  `($this->becoming)(new XxxInput(...))` → ドメイン例外を HTTP コードへマップ → `assert($final instanceof XxxFinal)`。`#[CsrfProtected]` 必須。
- **Be層**: Input(`#[Be(Final::class)]` readonly + `@psalm-taint-source`)→ Semantic(型検証、全 Input param に対応する Semantic を置く＝「0 notices」規約)→ Final(`#[Input]`/`#[Inject]`、AUTHZ は `AdminSession->adminId===null`)。
- **境界サービス**: port=`be/src/Reason/Service/`、実装=`src/Compatibility/Eccube/`、Fake=`tests/Fake/Reason/Service/`。`src/Module/AppModule.php` で `->to(Eccube実装)->in(SINGLETON)`、`src/Module/FakeModule.php` で `toInstance(Fake)`。
- **テスト**: domain=`be/tests/Domain/`、resource=`tests/Resource/`。`TestModule` + `AbstractModule` override で `AdminSession`（や他の Fake）を差し替える。

---

## 残作業（ローカルで実装）

### 1. doUpdateCsv 消費側配線（低リスク・推奨先行）

**現状**: `doUpdateCsv`（`be/src/Final/CsvConfigUpdated.php`）は dtb_csv カラム設定を
`CsvColumnConfigStorageInterface::replaceType` で**永続化済み**。しかし export Final 群
（`be/src/Final/AdminProductCsvExported.php` / `AdminCustomerCsvExported.php` / `AdminOrderCsvExported.php` /
`AdminShippingCsvExported.php`）は**ハードコード列**を出力し、設定を消費していない。

**検証済みの安全策**:
- `CsvColumnConfigStorageInterface::listByType(int $csvType): list<CsvColumnConfigEntity>` で取得。
  Entity = `{int csvType, string columnName, bool enabled, int sortNo}`。csvType: order=1/customer=2/product=3/shipping=4。
- Fake fixture `be/var/fake/query/csv_column_list_by_type.jsonl` は**空** → Fake では `listByType` は `[]`。
  （patch 済 ray/fake-query の list クエリは未マッチで `[]`、例外は row クエリのみ）
- 既存テスト（`be/tests/Domain/AdminProductCsvExportedTest.php` / `tests/Resource/AdminProductCsvResourceTest.php`）は
  現行ハードコード列の先頭 `productCode,productName` 等を assert している。

**設計方針**: 各 export Final に `CsvColumnConfigStorageInterface` を `#[Inject]`、`listByType` の
`enabled=true` を `sortNo` 昇順で列構成。**設定が空配列なら従来のハードコード列にフォールバック**
（→ 既存テスト不変）。columnName→値の対応は「列カタログ（既定列＝単一真実源）」を 1 箇所に持つのを推奨。
未知 columnName は空セル or 無視（要決定）。新規テストは Fake で設定を与えて列の絞り込み・並び替えを検証。

#### 検証済み詳細設計（Plan エージェント出力、実装前に codex 再レビュー推奨）

**対象 4 export Final の現状列（フォールバック時は byte 一致を維持＝絶対に変えない）**:

| Final | csvType | source | public prop | 既定列順 | 細部 |
|---|---|---|---|---|---|
| `AdminProductCsvExported` | 3 | `productQuery->listForExport()` | `csv` / `count` | productCode, productName, price02, stock, productStatus, description, searchWord, note | `php://memory`, `=== false` ガード, escape `'\\'`, private `encodeRow()` |
| `AdminCustomerCsvExported` | 2 | `customerQuery->search(null,null,5000)` | `csv` / `rowCount` | customerId, email, name01, name02, kana01, kana02, companyName, phoneNumber, postalCode, pref, addr01, addr02, customerStatus | `php://temp`, `assert`, escape `''`, 行リテラル inline |
| `AdminOrderCsvExported` | 1 | `orderQuery->list(1000,0)`（`FinalizedOrderEntity`） | `csv` / `rowCount` | orderNo, customerId, orderStatus, orderDate, total, paymentTotal, subtotal, deliveryFeeTotal, charge, discount, tax | `php://temp`, `assert`, escape `''` |
| `AdminShippingCsvExported` | 4 | `shippingAddresses->list()` | `csv` / `rowCount` | orderNo, name01, name02, postalCode, pref, addr01, addr02, phoneNumber, trackingNumber | `php://temp`, `assert`, escape `''`（trackingNumber は現状リテラル `''`） |

> **各 Final の差異（escape 文字・stream 種別・`count` vs `rowCount`・nullable の `?? ''`・`(string)` cast）は統一せず温存**すること。統一するとフォールバック byte 一致保証が崩れ diff が膨らむ。

**設計判断（推奨）**:
1. **列カタログは各 Final 内に閉じる**（global catalog 不採用）。理由: global catalog は 4 つの異なる Entity に紐づく値抽出 closure を 1 クラスに集約し「Final が自分の組み立てを所有する」Be 原則に反し、レビュー面（Order/Customer トリガ）も広がる。各 Final に `private const DEFAULT_COLUMNS = [...]` ＋ `columnMap(Entity $row): array<string,string|int>`（キー=列名、値=現 `encodeRow`/inline と同一式）を置き、**ヘッダと行が同じ map から出る**ことで現状の header/row 二重定義 drift を解消（地味な cleanup）。
2. **未知 columnName**（map に無い列名）: **ヘッダには出すが各行は空セル**（`$this->columnMap($row)[$name] ?? ''`）。EC-CUBE の寛容 export 準拠・行幅==ヘッダ幅維持・明示有効化列を黙殺しない。
3. **filter/sort**: `listByType` は sortNo 昇順済みだが結合度低減のため Final 側で `sortNo` 防御的 re-sort → `enabled===true` を `array_filter` → `array_values(array_map(columnName))`。

**実装手順（各 Final 共通）**:
- ctor に `#[Inject] CsvColumnConfigStorageInterface $csvColumnConfigStorage` 追加（AUTHZ 等不変）。
- `$config = $csvColumnConfigStorage->listByType(該当csvType);`
  `$columns = $config === [] ? self::DEFAULT_COLUMNS : array_values(array_map(fn($e)=>$e->columnName, array_filter(sortBySortNo($config), fn($e)=>$e->enabled)));`
- ヘッダ: `fputcsv($handle, $columns, ...)`、各行: `fputcsv($handle, array_map(fn($n)=>$this->columnMap($row)[$n] ?? '', $columns), ...)`。
- interface/entity/storage/Resource は**変更なし**（`listByType` 既存、public prop 不変）。
- psalm: `columnMap` は `@return array<string,string|int>`、行配列は `list<string|int>`。list 推論が崩れたら `array_values()` で包む。

**既存テスト非破壊の根拠**: Fake では `csv_column_list_by_type.jsonl` が空 → 全 csvType で `listByType` が `[]` → 全 Final がフォールバック → 現状と同一 byte。対象既存: `be/tests/Domain/AdminProductCsvExportedTest.php`, `tests/Resource/AdminProductCsvResourceTest.php`, `tests/Resource/AdminCustomerCsvResourceTest.php`。**注: order/shipping/customer の domain test は存在しない**（export domain test は product のみ）。`SqlCsvColumnConfigStorageTest` は影響なし。

**新規テスト方式**: `CsvColumnConfigStorageInterface` は FakeQuery 裏付けで手書き Fake が無い。よって `AdminSession` 差し替えと同じ **`TestModule` + 無名 `AbstractModule` override** で `bind(CsvColumnConfigStorageInterface::class)->toInstance($stub)`（無名クラスで `listByType` が固定ベクトルを返す／`replaceType` no-op）。Ray.Di override が FakeQuery バインドに勝つ。product で「並び替え＋disabled 除外＋未設定列除外」、customer で「`passwordHash` 名の列を設定しても空セル」を assert。共有 jsonl は**空のまま据え置く**（FakeQuery は `replaceType`→`listByType` を永続しないので jsonl-fixture 不採用、実永続化は SQL スイート担保）。

**セキュリティ（Order|Customer→条件付きレビュー発火）事前正当化**: (a) AUTHZ 不変、(b) 機微フィールドは抽出 map に無い＝敵対的設定でも出力不可、(c) 未知列は常に空セル。

**watch-items**: order export は `OrderEntity` でなく `FinalizedOrderEntity`（`order_list_all`＋`FinalizedOrderFactory`）。map を書く前に現 inline 行が参照するフィールド名が当該 entity に在ることを確認。

### 2. doCreateOrder enrichment（大・購入中核・要丁寧検証）

**現状**: `be/src/Final/AdminOrderCreated.php` は受注を**永続化済み**だが、ALPS doc が要求する
「PurchaseFlow で税・送料・在庫を計算」を**省略**（明細行なし、totals は入力値の素朴な加算）。

**残作業**: 管理画面手動受注に明細行（productClass × 数量）入力を追加し、`PurchaseFlowInterface` で
小計/税/送料を再計算、`InventoryAllocatorInterface` で在庫引当、明細 snapshot 行を永続化。
Pilot 5 `doCheckout`（`be/src/Final/` の checkout 系）が同型の正解パターン。既存部品:
`PurchaseFlowInterface` / `InventoryAllocatorInterface` / `OrderCommandInterface` / `OrderNoProvider`。

**注意**: 購入フロー中核に触れるため影響が大きい。マスアサインメント規律（Pilot 5 F-2）を維持。
**codex 事前相談 → Plan → 実装 → SQL スイート含む全検証**の順を強く推奨。

### スコープ外（やらない）
- `doImportProductCsv`: ルートが export 専用で意図的に未モデル化。
- `doInstallPlugin` / Owners Store（Super Hard 2件）: plugin scope 外（migration-status の方針）。
- 各副作用の **EC-CUBE 完全互換**（実ファイル書込・TOTP 永続・CSV バイト一致・本番DB bring-up）:
  production cutover の追跡残作業（PDF pilot と同じ扱い）。

## ローカル引き継ぎ手順

```bash
git fetch origin claude/gallant-fermat-CpUqD
git checkout claude/gallant-fermat-CpUqD
composer install        # ローカルは通常これで patch 適用（要 plugin 有効）
grep -c fakeDirs vendor/ray/fake-query/src/FakeQueryConfig.php   # >0 を確認
./vendor/bin/phpunit --testsuite fake                            # green を確認
# → codex 相談しつつ「残作業 1 → 2」の順で実装
```
