# 完全置換残差（Complete Replacement Residuals）

Last updated: 2026-06-04
Evidence snapshot: `1ccc7d5` (`origin/be-first-migration-bootstrap` after PR #34)

BeMart は、EC-CUBE 4.3 の機能領域をほぼ横断的にカバーしている。`migration-status.md` の Feature matrix と Phase log が示す通り、ALPS、Be domain、BEAR Resource、SQL、HTML の主要面は最終段階に達している。

したがって、残っている問題は **カバー率の問題ではない**。残っているのは、本番 EC-CUBE の完全代替に必要な **互換 fidelity** と **production verification** の問題である。

この文書は、その残差を詳細な台帳として整理する。

---

## 1. 互換忠実度（fidelity）残差

機能の意味、ルート、Resource 到達、または暫定 adapter は存在するが、EC-CUBE 4.3 の完全代替としては byte-level / side-effect-level / UI-data-level の忠実度がまだ不足しているもの。

| 領域 | 現状 | 完全代替に必要な残差 | 完了証拠 |
|---|---|---|---|
| PDF 帳票 | `goExportOrderPdf` は Resource 到達、download header、`%PDF-` body 生成まで実装済み。Issue #24 compatibility pilot として隔離済み。 | EC-CUBE 帳票レイアウト完全一致、`dtb_order_pdf` 保存設定の再現、複数配送テンプレート再現、TCPDF 出力差分の説明。 | EC-CUBE fixture との PDF smoke / layout comparison。複数配送注文での生成確認。 |
| CSV export | 商品・カテゴリ・規格・受注・配送などの export route は具体 Resource に接続済み。`doUpdateCsv` は保存済み column config を export Final が消費する。 | EC-CUBE 互換フォーマット、文字コード、ヘッダ、列順、quote/escape、streaming/download 境界、fixture による byte-level 比較。 | 代表 CSV ごとの EC-CUBE fixture 差分テスト。`dtb_csv` 設定あり/なし両方の通過。 |
| CSV import | `doImportCategoryCsv`、`doImportShippingCsv`、規格名/規格分類 CSV は実体化済み。`doImportProductCsv` は意図的に未移植。 | 商品 CSV import を完全代替対象に含めるなら、画像、カテゴリ、タグ、規格、商品クラス、税、在庫、公開状態の更新規則を EC-CUBE と合わせる。 | EC-CUBE 商品 CSV fixture の round-trip import/export。失敗行、部分更新、削除、validation の比較。 |
| Mail | Mail template editor は移植済み。各種 mail side-effect は service boundary に隔離されている。 | 本文生成、テンプレート解決、差し込み変数、送信先 fan-out、送信失敗時契約、履歴/ログの忠実再現。 | EC-CUBE mail body fixture との本文比較。注文・問い合わせ・パスワード再発行の送信経路テスト。 |
| Template 管理 | Template list/add は in-scope admin HTML として接続済み。select/delete/download/install route も concrete Resource へ接続済み。 | ZIP install/download/delete/select の file side-effect、default template 切替、validation、rollback、権限・CSRF 境界の忠実再現。 | 実 ZIP fixture による install/delete/download/select の end-to-end 確認。 |
| MasterData | `goMasterData` / `doSelectMasterData` / `doUpdateMasterData` は concrete Resource に接続済み。 | 任意 master table の schema 差分、入力型、破壊的更新の安全性、EC-CUBE と同じ編集制約。 | 代表 `mtb_*` の read/update fixture と rollback 確認。 |
| Product editor | 商品一覧、商品登録、商品編集、規格・カテゴリ・CSV editor は到達済み。 | 画像アップロード、カテゴリ/タグ、規格行列、在庫無制限、販売種別、通常価格、販売制限、発送日目安などの EC-CUBE 同等フォーム fidelity。 | EC-CUBE 実画面探索で確認した入力項目を BeMart HTML/Form/Resource body が満たすこと。 |
| Order editor | 受注一覧、受注編集、配送、メール、PDF、配送 CSV は到達済み。`doCreateOrder` / `doCheckout` は PurchaseFlow + `dtb_order_item` snapshot に収束済み。 | 受注新規、検索条件、配送/明細/支払/対応状況/メール履歴、PDF/CSV/Mail side-effect の EC-CUBE 忠実再現。 | admin order fixture の作成・更新・配送・メール・PDF/CSV 一連 workflow。 |
| Customer editor | 会員一覧、編集、配送先編集は到達済み。 | 会員新規、詳細検索、購入履歴、配送先一覧、お気に入り、ステータス操作の UI-data fidelity。 | EC-CUBE admin customer 探索項目に対応する form/render/resource tests。 |
| Storefront enrichment | Storefront は全ページ側をカバー。Cart、Mypage History、Shopping confirm/complete は enrichment 済み。 | Mypage dashboard、Favorite、Address、Contact の resource body enrichment。EC-CUBE テンプレートに必要なデータを捏造せず再導出する。 | render-diff residual shrink。各ページの Fake/SQL body が EC-CUBE Twig 要求を満たすこと。 |
| Block dynamic regions | logo/footer など shared Block は port 済み。 | cart totals、login/customer auth、search、category tree など動的 Block の EC-CUBE runtime 相当表示。 | storefront browser smoke と block-specific render tests。 |
| 2FA setup | TwoFactorAuth set route は接続済み。 | pre-auth setup で client supplied `loginId` / candidate secret を信用しない。server generated secret と pending login identity を challenge session に固定し、`TwoFactorAuthSet::onPut` で消費する。 | 他 admin の `loginId` を指定して secret を置換できない security regression test。 |

---

## 2. 本番検証（production verification）残差

実装または設計は存在するが、完全代替として宣言するには本番想定環境での強い証拠が必要なもの。

| 領域 | 現状 | 必要な検証 | 完了証拠 |
|---|---|---|---|
| SQL suite | SQL suite は MariaDB / `DATABASE_URL` 依存。非 SQL suite は 2026-06-01 に green baseline 記録あり。 | MariaDB 10.11 target engine で `DATABASE_URL=... vendor/bin/phpunit --testsuite sql` を green にする。 | target MariaDB で SQL suite green のログ。skip ではなく実実行。 |
| `order_item_register.sql` | `doCreateOrder` / `doCheckout` は order item snapshot を書く設計。SQL file は `JSON_TABLE` を使う。 | MariaDB 10.11 で `JSON_TABLE` が期待通り動くか確認。動かない場合は MariaDB 互換 INSERT に書き換える。 | `SqlOrderItemCommandTest` と checkout/admin order snapshot tests の SQL green。 |
| Production DB bring-up | seed script と prod `SqlModule` binding はある。 | EC-CUBE schema + `mtb_*` seed + prod context の起動確認。 | clean DB から setup script 実行、prod context smoke、主要 read/write route の確認。 |
| Prod context wiring | `SqlModule` / `MediaQueryRuntimeModule` は prod SQL binding を持つ。 | Fake が prod path に混入しないこと、log/side-effect が prod 契約に沿うこと。 | prod module tests が live DB で green。Fake storage 参照がないことの regression test。 |
| Render-diff | HTML render tests は `tools/ec-cube-source/` 4.3 clone がある時に EC-CUBE 実テンプレートと比較する。 | EC-CUBE source clone ありで render-diff suite を再実行し、residual allowlist が最新であることを確認する。 | render-diff tests green。残差 allowlist が説明付きで残る。 |
| HTTP / Hypermedia | `tests/Hypermedia/WorkflowTest.php` と `tests/Http/WorkflowTest.php` が同一 workflow を in-process / real HTTP で検証する。 | 最新 base で fake/http/smoke suite を再実行する。cookie/session 境界の回帰を捕捉する。 | `composer test:fake`, `composer test:http`, `composer test:smoke` green。 |
| Browser smoke | 2026-05-23 の実サイト探索と browser verification JSON がある。 | 最新 base で storefront/admin の主要導線を再 smoke。Feature status の browser column を更新する。 | `docs/eccube-feature-browser-verification.json` と生成 HTML の確認済み行更新。 |
| Compatibility adapters | PDF/CSV/Mail/Template/MasterData などは adapter boundary に隔離済み。 | adapter が本番副作用を起こす経路で、file/network/db/error handling の契約を確認する。 | adapter ごとの fixture / failure-mode tests。 |
| Security production cutover | CSRF、session、2FA、admin auth は route-level に接続されている。 | pre-auth 2FA challenge、secure cookie、strict session、CSRF token 発行/検証、admin authorization を本番設定で確認する。 | security regression tests と production config checklist。 |

---

## 3. スコープ外（out of scope）

以下は完全代替の残差ではなく、明示的に切った境界である。完全代替の定義を変更しない限り、この台帳の「残作業」としては数えない。

| 領域 | 扱い |
|---|---|
| Plugin runtime / marketplace | `doInstallPlugin` は stub / out-of-scope。download、unzip、migration、container rebuild は今回の semantic migration 実証には含めない。 |
| Store/Plugin install/search subtree | admin plugin install/search 画面群は out-of-scope。plugin runtime を含めないため。 |
| Anti-Corruption Layer 研究 | EC-CUBE runtime を恒久的に同居させる構想は別研究テーマ。 |

---

## 4. 優先順位

完全代替へ進む場合は、機能追加より先に production verification を閉じる。

1. MariaDB 10.11 で SQL suite を green にする。
2. `order_item_register.sql` の `JSON_TABLE` portability を確定する。
3. production DB bring-up を実施する。
4. 2FA pre-auth challenge state を閉じる。
5. render-diff / browser smoke を最新化する。
6. PDF / CSV / Mail / Template / MasterData の順で compatibility fidelity を詰める。
7. Mypage dashboard / Favorite / Address / Contact の HTML enrichment を進める。

この順序にする理由は、Feature matrix のカバー率はすでに十分高く、残りのリスクは「動線がない」ことではなく「本番互換として証明できていない」ことにあるためである。
