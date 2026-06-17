# EC-CUBE 結合試験項目書 × BeMart カバレッジ — 実行ブリーフ（executor 用）

このファイルは **実行モデル（DeepSeek 等、安価モデル想定）** に渡す自己完結タスク。
判定（カバー率）は行わない。証拠を採取するだけ。判定は別モデルが [`VERIFIER-BRIEF.md`](VERIFIER-BRIEF.md) で行う。

## 0. あなたの役割と鉄則（最重要）
あなたは **実行者（executor）**。**カバー/未カバーの判定はしない**。観測した事実と証拠だけを記録する。

- 鉄則1: `verifier_status` は **必ず `null`**。`observed` は事実のみ（例:「501 を返した」「カート画面に商品1件が表示された」「/admin/login へ redirect」）。評価語（対応済み等）を書かない。
- 鉄則2: **実(SQL)コンテキストのみ**で実行する。fake コンテキストや 2FA の `123456` は使わない（偽陽性になる）。
- 鉄則3: BeMart のコードを**修正しない**。エラー・500・501 も**そのまま証拠として記録**する（重要な発見）。
- 鉄則4: 項目を黙って飛ばさない。実行不能は `executed:false` ＋ `reason` を記録。
- 鉄則5: 期待結果（`期待結果`）は**原文（日本語）を逐語コピー**。要約しない。
- 鉄則6: HTTP status を正直に記録。

## 1. ゴール
EC-CUBE の 17 個の結合試験項目書の各「試験項目」を BeMart 上で実行し、**操作トレース＋HTTP レスポンス＋（可能なら）スクリーンショット**を証拠として残し、1項目1レコードの JSONL を出力する。

## 2. 入力（試験項目書 17 本）
```bash
git clone --depth 1 -b 4.0 https://github.com/EC-CUBE/eccube-specification /tmp/eccube-spec
ls /tmp/eccube-spec/IntegrationTest
# EA01..EA10 = 管理画面 / EF01..EF07 = フロント
```
各 md は表形式の試験項目集。`項目ID`・`手順`・`期待結果` を抽出すること。

## 3. 対象（BeMart）の起動 — 実(SQL)コンテキスト
作業コピー: `/Users/akihito/git/BeMart`（cwd とする）
```bash
composer install
malt start && source <(malt env)
export DATABASE_URL='mysql://root@127.0.0.1:3306/eccubedb?charset=utf8mb4'
sql/setup-db.sh "$DATABASE_URL"        # DB を seed 状態へ
composer serve:page                    # http://127.0.0.1:8081 (HTML)
```
seed 管理者: `test-admin` / `local-dev-admin-password`

### 3a. 共通（CSRF とセッション）— 全 POST/PUT で必須
- POST/PUT は hidden の `csrfToken` を必ず同送する（無いと **403 Invalid or missing CSRF token**）。**直前の GET ページの HTML から値を抜いて**送る:
  ```bash
  JAR=/tmp/bemart.cookies
  CSRF=$(curl -s -b "$JAR" -c "$JAR" "http://127.0.0.1:8081/<フォームのGETページ>" \
    | grep -oE 'name="csrfToken"[^>]*value="[^"]*"' | grep -oE 'value="[^"]*"' | head -1 \
    | sed -E 's/value="(.*)"/\1/')
  ```
- セッションは cookie で持つ。**GET も POST/PUT も常に同じ `-b "$JAR" -c "$JAR"`** を付ける（付け忘れると毎回ログインを要求される）。

### 3b. 管理画面(EA)ログイン
**既定（推奨）: dev ログインで 2FA をバイパスする。** `composer serve:page:dev`（＝ `BEMART_DEV_LOGIN=1` 付き起動）で立てると、admin は **2FA コード `123456`** で通る（**実 SQL データはそのまま**、2FA サービスだけ差し替え）。本番では無効（`cli-server` かつ env かつ非prod のときのみ有効）。
```bash
JAR=/tmp/bemart.cookies
# 1) POST /admin/login (loginId=test-admin, password=local-dev-admin-password, csrfToken=§3aで抽出) → /admin/two-factor-auth へ redirect
# 2) POST /admin/two-factor-auth (deviceToken=123456, csrfToken) を同じ JAR で → 認証完了
# 3) 以後の EA リクエストは同じ JAR を使い回す（context は "sql" のまま。dev ログインで実行した項目は notes に auth=dev-bypass と記録）
```

**例外: EA02 Authentication の項目は dev ログインを使わない。** 2FA 自体をテストする項目で `123456` を使うと「2FA が動く」の偽 Covered になる。EA02 は通常起動 `composer serve:page`（実 TOTP）で実行し、authKey から都度6桁を計算する（notes に `auth=real-totp`）。参考: `tests/Http/HttpAdminLoginCookieFlowTest.php`。
```bash
composer admin -- reset-2fa test-admin   # 2FA を初期化（次ログインで登録画面へ）
# 1) POST /admin/login → /admin/two-factor-auth-set へ redirect
# 2) GET /admin/two-factor-auth-set の hidden input authKey（= secret）を取得
# 3) authKey から現在の6桁を計算:
SECRET='<authKey>' php -r 'require "vendor/autoload.php";
$s=new class implements \MyVendor\BeMart\Be\Reason\Query\TwoFactorAuthStorageInterface{
 public function secret(string $l):\MyVendor\BeMart\Be\Reason\Query\Result\TwoFactorAuthSecret{return new \MyVendor\BeMart\Be\Reason\Query\Result\TwoFactorAuthSecret(null);}
 public function enable(string $l,string $x):void{}};
echo (new \MyVendor\BeMart\Compatibility\Eccube\EccubeTwoFactorAuth($s))->generateDeviceToken(getenv("SECRET")),"\n";'
# 4) PUT /admin/two-factor-auth-set (deviceToken=6桁, csrfToken) を同じ JAR で送る → 認証完了
```
コードは配信されない＝同じ authKey から都度計算する。

### 3c. フロント(EF)・会員ログイン、ルート探索
- 多くの EF 項目は匿名（トップ/商品/カート）。会員系（EF04 Customer・EF05 Mypage）は項目の手順どおり**会員登録 → ログイン**を行い、同じ JAR を使う。
- **URL を推測で捏造しない**。EC-CUBE 画面名 → BeMart の URL は `docs/eccube-feature-alps-status.html`（route 列）と `src/Resource/Page/` で特定する。
- 未実装は 501 や `/__not-implemented?route=...` になる → エラーではなく **Gap の証拠**として記録し次へ（直さない）。

## 4. 1項目ごとの手順
1. 試験項目書から `項目ID`・`手順`・`期待結果` を取り出す。
2. 手順どおり BeMart を操作（curl は `-i -b $JAR -c $JAR`、HTML サーバ 8081）。
3. 証拠を保存（下記レイアウト）: レスポンス本文(HTML)、ステータス/ヘッダ、可能なら画面スクショ。
4. `observed` に事実を1〜2文で記録。`verifier_status` は `null` のまま。
5. メール送信・DB 状態・税計算など**画面に出ない期待結果**は、補助証拠（レスポンス本文・必要なら `mysql` での該当行 SELECT 結果）も保存し observed に明記。

## 5. 出力
出力先（BeMart リポジトリ内、検証者が読めるように）:
```
docs/eccube-spec-coverage/
  records/<AREA>.jsonl                       # 1項目1行
  evidence/<AREA>/<ITEM_ID>.response.html
  evidence/<AREA>/<ITEM_ID>.headers.txt
  evidence/<AREA>/<ITEM_ID>.png             # スクショ可能なら
```
JSONL 1行スキーマ（1項目）:
```json
{"area":"EF03","item_id":"EF03-012","title":"カートに商品を入れる","expected":"<期待結果を原文で逐語>","context":"sql","executed":true,"steps":[{"method":"POST","url":"/cart/item","inputs":{"productCode":"sample-001","quantity":"1"}}],"http_status":303,"content_type":"text/html; charset=utf-8","evidence":{"response":"docs/eccube-spec-coverage/evidence/EF03/EF03-012.response.html","headers":"docs/eccube-spec-coverage/evidence/EF03/EF03-012.headers.txt","screenshot":"docs/eccube-spec-coverage/evidence/EF03/EF03-012.png"},"observed":"303 で /cart へ redirect、/cart に商品1件表示","verifier_status":null,"notes":""}
```

## 6. 進め方の順序
1. **EF01–EF07（フロント）から**（ログイン不要で摩擦が小さい）。
2. 次に **EA01–EA10（管理画面）**（3a のログインを1回確立して cookie 使い回し）。EA は 501/未実装が多い見込み → それも正直に記録（`http_status:501`, `observed:"未実装ページ"`）。

## 7. トラブルシューティング
| 症状 | 原因 / 対処 |
|---|---|
| `DB接続に失敗` / 「該当する管理者が見つかりません」 | malt 未起動 or seed 未投入。`malt start` → `sql/setup-db.sh "$DATABASE_URL"` |
| 起動時 `DATABASE_URL is not set` 例外 | `export DATABASE_URL=...` 忘れ（実コンテキストは必須） |
| ポート 8081 使用中 | 既存プロセス停止 or 別ポートで serve し、URL を合わせる |
| POST/PUT が **403** (`Invalid or missing CSRF token`) | csrfToken 未送 or cookie jar 不一致 → §3a で抽出して同送、全リクエストに同じ jar |
| 2FA コード拒否 (**400**) | 時刻ずれ / authKey 取り違え / 30秒窓。**同一マシンで即計算→即送信**。authKey はそのセッションの hidden input から |
| レスポンスが **JSON** (`{"code":...}`) で来る | API(8080) を叩いている。**HTML は serve:page(8081)**、html context で実行する |
| **501** / `/__not-implemented` | エラーではなく **Gap の証拠**。`http_status:501` で記録して次へ（直さない） |
| 毎回ログインを要求される | cookie jar の付け忘れ。GET/POST/PUT 全部に `-b -c` |
| 画面は出るが期待結果の状態にならない | 手順の前提（seed・先行操作）未達か。満たせなければ `executed:false`+reason、または observed に「期待状態に到達できず」と明記（判定はしない） |

## 8. やってはいけない
- カバー判定を書く（`verifier_status` を埋める）/ 評価語を使う
- fake コンテキスト・`123456` を使う / BeMart を修正する
- 期待結果を要約する / 項目を黙って飛ばす / 501・404 を「無かったこと」にする

## 9. 納品物
`docs/eccube-spec-coverage/records/*.jsonl` ＋ `evidence/` 一式。
最後に「領域ごとの実行件数・`executed:false` 件数・status 分布」だけ集計して報告（**カバー率は出さない**＝検証者の仕事）。
