# EC-CUBE 結合試験項目書 × BeMart カバレッジ — 実行ブリーフ（executor 用）

このファイルは **実行モデル（DeepSeek 等、安価モデル想定）** に渡す自己完結タスク。
判定（カバー率）は行わない。証拠を採取するだけ。判定は別モデルが [`VERIFIER-BRIEF.md`](VERIFIER-BRIEF.md) で行う。

## 0. あなたの役割と鉄則（最重要）
あなたは **実行者（executor）**。**カバー/未カバーの判定はしない**。観測した事実と証拠だけを記録する。

- 鉄則0（最重要）: **「実行」とは手順の操作を実際に行うこと。** 対象 URL を開いただけ／200 が返っただけは「実行」ではない。検索・登録・編集・削除・送信・多段遷移は、**フォームに入力して送信ボタンを押し、遷移先まで到達**し、**期待結果が実際に起きたかを画面（スクショ＋DOM）で確認**する。**HTTP 200 ≠ 期待結果達成。** 操作していない項目は `executed:false`（reason 必須）。
- 鉄則1: `verifier_status` は **必ず `null`**。`observed` は事実のみ（例:「カート画面に商品1件が表示」「『入力してください』が表示」「/admin/index へ遷移」）。評価語（対応済み等）を書かない。
- 鉄則2: **実(SQL)コンテキスト**で実行する。fake コンテキストは使わない（偽陽性）。2FA は **dev バイパス（`123456`）を既定で使ってよい**（`BEMART_DEV_LOGIN=1`、実 SQL データはそのまま 2FA サービスだけ差替）。**例外: EA02（2FA 自体の試験）は実 TOTP**（§3b）。
- 鉄則3: BeMart のコードを**修正しない**。エラー・500・501 も**そのまま証拠として記録**する（重要な発見）。
- 鉄則4: 項目を黙って飛ばさない。実行不能は `executed:false` ＋ `reason`。
- 鉄則5: 期待結果（`期待結果`）は**原文（日本語）を逐語コピー**。要約しない。
- 鉄則6: HTTP status を正直に記録。
- 鉄則7: **全項目でスクリーンショット(.png)を必ず残す**（人が感覚的に判断できるように）。操作項目は操作後の状態を、閲覧項目はその画面を撮る。

## 1. ゴール / 方式（合意済み・厳守）
**方式: ブラウザ自動操作（Puppeteer ハーネス）。** 各「試験項目」を**実ブラウザで手順どおり操作**し、項目ごとに **スクショ(.png)＋レンダリング後DOM(.html)＋HTTP status** を採取して 1項目1レコードの JSONL を出力する。

- ハーネス本体・セットアップ・落とし穴・シナリオ書式は **[`harness/README.md`](harness/README.md) が正**。まずこれを読むこと。
- `curl` で GET して済ませる旧方式は**禁止**（行動を何も検証できないため。鉄則0）。
- ハーネスが自動で面倒を見ること（自分で再実装しない）:
  - **CSRF**: フォーム送信時にブラウザが hidden `csrfToken` を同送（手動抽出不要）。
  - **セッション**: 1ブラウザ内で cookie 継続。`auth:"admin"` は一度だけログインして使い回す。
  - **ログイン＋2FA**: `adminLogin()`（ID/PW→2FA `123456`→`/admin/index`）。
  - **PRG**: BeMart の状態遷移は **`200 + Location`（302ではない）**。`submitFollow` が Location を手動追従。
  - **prefill**: 管理ログインフォームは値が prefill 済 → `fill` は入力前にクリア（追記すると `test-admintest-admin` で 401）。

## 2. 入力（試験項目書 17 本）
```bash
git clone --depth 1 -b 4.0 https://github.com/EC-CUBE/eccube-specification /tmp/eccube-spec
ls /tmp/eccube-spec/IntegrationTest   # EA01..EA10 = 管理 / EF01..EF07 = フロント
```
各 md は表形式の試験項目集。`項目ID`・`手順`・`期待結果` を抽出する。
（パース済みの一覧が [`all_items.json`](all_items.json) にある＝248項目。`steps` は人間向け手順文なので、ブラウザ操作へ翻訳して使う。）

## 3. 対象（BeMart）の起動 — 実(SQL)コンテキスト＋ハーネス
作業コピー: `/Users/akihito/git/BeMart`（cwd）
```bash
composer install
malt start && source <(malt env)
export DATABASE_URL='mysql://root@127.0.0.1:3306/eccubedb?charset=utf8mb4'
sql/setup-db.sh "$DATABASE_URL"                         # スキーマ＋master seed

# ★ ログイン PoC が使う管理者を必ず確認/投入（重要）
#   analysis-sample は admin1..5(SHA256, ログイン不可)を入れる。bcrypt の test-admin が
#   無いと管理ログインは 401 になる。idempotent UPSERT で投入:
mysql -h127.0.0.1 -uroot eccubedb < sql/seed/dtb-system-master.sql
mysql -h127.0.0.1 -uroot eccubedb -e \
  "SELECT login_id FROM dtb_member WHERE login_id='test-admin';"   # 1行返れば OK

# ★ ログイン PoC が使う会員も投入（会員系 EF04/EF05 で必須）。front Login の PoC は
#   login-test@example.com を期待するが未 seed。既存顧客 id=1 を既知 bcrypt＋本会員に更新:
HASH=$(php -r "echo password_hash('local-dev-member-password', PASSWORD_BCRYPT, ['cost'=>12]);")
mysql -h127.0.0.1 -uroot eccubedb -e \
  "UPDATE dtb_customer SET email='login-test@example.com', password='$HASH', salt=NULL, customer_status_id=2 WHERE id=1;"

composer serve:page:dev                                # http://127.0.0.1:8081（dev 2FA バイパス付）
cd docs/eccube-spec-coverage/harness && npm install    # puppeteer-core（要 Google Chrome）
```
seed 認証情報（すべて検証済・実動確認済）:
- 管理者: `test-admin` / `local-dev-admin-password`、dev 2FA コード: `123456`
- 会員: `login-test@example.com` / `local-dev-member-password`（本会員）
- 公開商品コード: 偶数（`CODE000002, 004, …`）

実証済みテンプレ（複製して使う。CSS セレクタが脆い多段フローは `clickText`/`selectFirst` を使う）:
- **ゲスト購入→注文完了**（実受注を生成）: `harness/scenarios-checkout.json`
- 会員ログイン→マイページ: `goto /login → fill email/password → submitFollow 'form[action="/login"] button' → goto /mypage`
- 管理ログイン: `auth:"admin"`（ハーネスが自動）

### 3b. 例外: EA02 Authentication は dev バイパスを使わない
2FA 自体をテストする項目で `123456` を使うと「2FA が動く」の偽 Covered になる。EA02 は通常起動
`composer serve:page`（実 TOTP）で、authKey から都度6桁を計算して `deviceToken` に入れる（notes に `auth=real-totp`）。
参考: `tests/Http/HttpAdminLoginCookieFlowTest.php`。secret→6桁の計算:
```bash
SECRET='<authKey(=登録画面 hidden input)>' php -r 'require "vendor/autoload.php";
$s=new class implements \MyVendor\BeMart\Be\Reason\Query\TwoFactorAuthStorageInterface{
 public function secret(string $l):\MyVendor\BeMart\Be\Reason\Query\Result\TwoFactorAuthSecret{return new \MyVendor\BeMart\Be\Reason\Query\Result\TwoFactorAuthSecret(null);}
 public function enable(string $l,string $x):void{}};
echo (new \MyVendor\BeMart\Compatibility\Eccube\EccubeTwoFactorAuth($s))->generateDeviceToken(getenv("SECRET")),"\n";'
```

### 3c. URL を推測で捏造しない（重要・実際に踏んだ罠）
EC-CUBE 画面名 → BeMart の URL は `src/Resource/Page/`（クラス名＝kebab の URI。例 `Admin/ProductList` → `/admin/product-list`）と
`docs/eccube-feature-alps-status.html`（route 列）で特定する。**推測パスは 404 になる**。
- 例: フロント商品詳細は **`/product?productCode=<code>`（クエリ param）**。`/product/<code>`・`/products/detail/<id>` は **404**。
- 公開商品の `productCode` は **偶数**（`CODE000002, 4, 6, …`。奇数は非公開 status=2 で詳細 404）。**最も確実なのは `/products` 一覧の実リンク（`a[href*="productCode"]`）を辿る**こと。ブラウザ操作なら「一覧→商品をクリック」で URL を推測せず到達できる。
- カート投入フォーム: 商品詳細内の `<form action="/cart/item">`（hidden `productCode`＋`csrfToken`、`quantity`、`button.add-cart`）。`submitFollow` で送る（CSRF は自動同送）。
未実装は 501 や `/__not-implemented?route=...` → **エラーでなく Gap の証拠**として記録し次へ（直さない）。

## 4. 1項目ごとの手順
1. 試験項目書から `項目ID`・`手順`・`期待結果` を取り出す。
2. **手順を `scenarios.json` の steps に翻訳**する（書式・action 一覧は harness/README.md）。型:
   - 検索: `goto`（一覧）→ `fill`/`select`（条件）→ `submitFollow`（検索）→ 結果を確認
   - 登録/編集: `goto`（フォーム）→ `fill`/`select`/`check` → `submitFollow` → 完了 or エラーを確認
   - 削除: 対象を作成/特定 → 削除を `submitFollow` → 消えたことを確認
   - 多段（カート→確認→確定 等）: 各段を順に `fill`/`submitFollow` で最終状態まで到達
   - 単純閲覧のみの項目だけ `goto` 1回で可
3. ハーネス実行（項目単位 or 領域単位）。`area`/`item_id` は**結合試験の項目ID**にする（例 `EA03`/`EA03-1`）＝検証者が証拠→仕様を対応づけられる。
   ```bash
   node run.cjs my-scenarios.json     # → ../evidence-browser/<AREA>-<ITEM_ID>.png/.html ＋ browser-run.jsonl
   ```
4. 採取された **スクショ(.png)＋DOM(.html)＋status** を確認。`observed` は**期待結果が起きたか**を事実で書く（「200」ではなく「検索結果に商品Xが表示」「『入力してください』が表示」「/complete へ遷移」）。
5. 画面に出ない期待結果（メール送信・税計算・DB状態）は補助証拠（DOM の該当箇所／必要なら `mysql` の該当行 SELECT）を残し observed に明記。
6. `expected`（逐語）・`steps`（実際に行った操作）・`observed`・`http_status` を JSONL に書く。`verifier_status` は `null`。
7. **自己チェック（必須）**: その項目の steps が `goto` 1回だけなのに、期待結果が操作（検索/登録/編集/削除/送信）を要するなら**未実行**。`executed:false`＋reason にするか、やり直す。

## 5. 出力
```
docs/eccube-spec-coverage/
  evidence-browser/<AREA>-<ITEM_ID>.png     # 操作後/対象画面のスクショ（人が判断）
  evidence-browser/<AREA>-<ITEM_ID>.html    # レンダリング後DOM（機械検証）
  evidence-browser/browser-run.jsonl        # ハーネスが出す素の記録（id/url/http_status/verifier_status:null）
  records/<AREA>.jsonl                       # ↓ のスキーマで項目ごとに追記（expected/steps/observed を付与）
```
`records/<AREA>.jsonl` 1行スキーマ（1項目）:
```json
{"area":"EF03","item_id":"EF03-1","title":"カートに商品を入れる","expected":"<期待結果を原文で逐語>","context":"sql","executed":true,"auth":"none","steps":[{"action":"goto","url":"/products/detail/2"},{"action":"submitFollow","sel":"form.add-cart button"}],"http_status":200,"evidence":{"png":"evidence-browser/EF03-EF03-1.png","html":"evidence-browser/EF03-EF03-1.html"},"observed":"カート画面に商品『…』が数量1で表示","verifier_status":null,"notes":""}
```

## 6. 進め方の順序
1. **EF01–EF07（フロント）から**（ログイン不要で摩擦が小さい）。会員系（EF04/EF05）は手順どおり会員登録→ログイン。
2. 次に **EA01–EA10（管理画面）**（`auth:"admin"` で1回ログイン→使い回し。EA02 のみ §3b 実 TOTP）。501/未実装が出たら正直に記録（`http_status:501`, `observed:"未実装"`）。

## 7. トラブルシューティング
| 症状 | 原因 / 対処 |
|---|---|
| 管理ログインが **401**（/admin/login に戻る） | (a) `test-admin` が DB に無い → §3 の `dtb-system-master.sql` を投入。(b) prefill にタイプして値が二重 → `fill` がクリアしてるか確認（harness の `fill`） |
| ログイン後に画面が変わらない（200 のまま） | PRG（`200+Location`）未追従。送信は `submitFollow` を使う（`click` ではなく） |
| 2FA コード拒否（**400**） | EA02 で時刻ずれ/authKey 取り違え/30秒窓。**即計算→即送信**。dev バイパスなら `123456`（`serve:page:dev` 必須） |
| `DATABASE_URL is not set` 例外 | `export DATABASE_URL=...` 忘れ（実コンテキストは必須）。`bin/admin.php` 等も同様 |
| レスポンスが **JSON** | API(8080) を叩いている。HTML は **serve:page:dev(8081)** |
| **501** / `/__not-implemented` | エラーでなく **Gap の証拠**。記録して次へ（直さない） |
| Chrome が見つからない | `CHROME_BIN` で実体パスを指定（既定 `/Applications/Google Chrome.app/Contents/MacOS/Google Chrome`） |
| 一覧が空に見える | データは seed 済（`analysis-sample.sql`: 商品2000=公開1000・会員500・カテゴリ50。商品コード/価格/在庫/visible 付きで表示・カート投入まで動く）。**受注のみ 0 件**＝購入フロー項目(EF)で生成されるか、無ければ observed に「受注データ未生成」と明記。商品が出ないなら 3c の URL/公開状態を疑う |

## 8. やってはいけない
- カバー判定を書く（`verifier_status` を埋める）/ 評価語を使う
- fake コンテキストを使う / EA02 で `123456` を使う / BeMart を修正する
- 期待結果を要約する / 項目を黙って飛ばす / 501・404 を「無かったこと」にする
- **手順の操作をせず単一 GET（`goto`1回）で操作項目を済ませる**（200 を「実行/達成」と見なす）
- **スクリーンショットを残さない**（鉄則7）

## 9. 納品物
`docs/eccube-spec-coverage/evidence-browser/*`（png/html/jsonl）＋ `records/*.jsonl` 一式。
最後に「領域ごとの実行件数・`executed:false` 件数・status 分布」だけ集計して報告（**カバー率は出さない**＝検証者の仕事）。
