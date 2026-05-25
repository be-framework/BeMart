# HOW_TO_CONTINUE.md

別マシンでこの BeMart プロジェクトの作業を再開するための完全ガイド。
最終更新: 2026-05-18 (Slice 9 完了直後)

---

## 0. 現状サマリ

- **ブランチ**: `be-first-migration-bootstrap` (Slice 8 / 9 は同ブランチ上で進行)
- **リモート**: `https://github.com/koriym/ec-cube-alps.git`
- **最終 slice**: Phase B Slice 9 — Taint annotation (Pilot 1-5 全体)
- **テスト**: 90 tests, 205 assertions すべて green
- **Psalm / Psalm-taint**: errors なし (※ Slice 9 の honest finding 参照)

進捗の正は `HANDOVER.md` (1400 行超)。Phase A の Pilot 1-5 + Phase B の
Slice 1-9 の全 decision log と積み残しが入っている。新マシン側 Claude が
最初に読むべき唯一のドキュメント。

---

## 1. 新マシンでのセットアップ

### 1.1 取得

```bash
git clone https://github.com/koriym/ec-cube-alps.git
cd ec-cube-alps
git checkout be-first-migration-bootstrap
```

### 1.2 依存インストール

```bash
composer install
```

`require` の主要 dep:

- `bear/sunday`, `bear/package` (BEAR.Sunday)
- `be-framework/be` (Be Framework core)
- `my-vendor/be-mart-be` (このリポジトリのドメイン層 — `be/` サブツリーに同居)

PHP 8.4 で開発・テストしている。PHP 8.5 でも問題なし。

### 1.3 env / var の準備

`.gitignore` にあるものは新マシンで生成が必要:

```text
/vendor/           ← composer install で自動
/.phpunit.cache/   ← phpunit 実行で自動
/var/log/*.json    ← bin/app.php 実行時に作られる
/var/tmp/          ← 必要なら mkdir -p var/tmp/test
```

env.json は **このリポジトリでは現状使っていない** (`composer.json` に
`koriym/env-json` の dep が入っていない)。HANDOVER の Pilot 各章で
APP_CONTEXT 環境変数だけで切替している。テンプレ作成不要。

### 1.4 動作確認

```bash
composer test         # 73 tests / 181 assertions が緑なら OK
composer psalm        # No errors found! が出れば OK
composer psalm-taint  # 同上
```

`composer.json` の `scripts`:

- `test` — PHPUnit
- `psalm` — 通常 Psalm 解析
- `psalm-taint` — taint mode (Slice 1 で導入)
- `stree` — symbol-tree (補助)

cs-fix スクリプトは無い。フォーマットは手動 or PHPStorm の auto-format。

---

## 2. Claude Code 起動時の最初の prompt

新マシン側で `claude` を起動したら、最初のメッセージとして以下を貼り付ける:

```text
このリポジトリは BeMart (EC-CUBE → BEAR.Sunday + Be Framework 移植 PoC) です。
別マシンから作業を引き継ぎました。HOW_TO_CONTINUE.md と HANDOVER.md を読んで
現状を把握してください。

Phase B は Slice 9 (Taint annotation, BEAR 側のみ) まで完了しています。
重要: Slice 9 で「Be Framework の #[Be] chain が Psalm に opaque」 という
honest finding がありました (HANDOVER 内 "Slice 9 の現状認識" 参照)。
次は以下のいずれかから選びたい:

  A. Slice 10: 存在オラクル軽減 (404 と 403 の統一)
  B. Slice 11: Be Framework Psalm plugin (Slice 9 の opacity 問題への対策。
     plugin で `#[Be]` chain を辿る、または per-class manual propagation)
  C. Slice 7.2 / 8.2 統合 EC-CUBE 側 EventListener (customer_id + _csrf_token
     を 1 つの Symfony EventListener で mirror。本番 AUTHZ + CSRF が初めて
     機能する。Phase 2 キックオフ)

どれを推奨するか、判断基準を出してください。
```

判断基準は HANDOVER の Slice 9 セクション 末尾 (「次の Slice (Slice 10 以降)」
表) にも書いてあるが、改めて出してもらう方が再ロード後の整合確認になる。

---

## 3. 必読ファイル (Claude が最初に読む順)

1. **`HANDOVER.md`** — 構築過程の全記録。Pilot 1-5 (Phase A) と
   Slice 1-9 (Phase B) の decision log・積み残し・トレードオフ。
   読まなければ context 復元は不可能。
2. **`CLAUDE.md`** — プロジェクト固有の規約 (ALPS が source of truth、
   `/run migrate` ワークフロー、参照パターン 8 種)。
3. **`alps.json`** — 413 descriptor の EC-CUBE 4.3 セマンティクス定義。
   コード生成・移植の入力。
4. `.claude/workflows/migrate.json` — `/run migrate <descriptor-id>` の
   ステップ定義 (alps-analyze → domain → domain-review → application →
   application-review → security)。

---

## 4. リポジトリ構造の要点

```text
ec-cube-alps/
├── alps.json                 # source of truth (EC-CUBE 4.3 ALPS)
├── HANDOVER.md               # 全工程の決定記録 (最重要)
├── HOW_TO_CONTINUE.md        # このファイル
├── CLAUDE.md                 # プロジェクト規約
├── docs/                     # 生成 HTML / SVG (asd output)
├── src/                      # BEAR.Sunday アプリ層
│   ├── Auth/                 #   Session adapter (Slice 7) + CSRF adapter (Slice 8)
│   ├── Module/               #   Ray.Di Module (AppModule / ProdModule)
│   ├── Resource/             #   ResourceObject (page://*, app://*)
│   └── ...
├── be/                       # Be Framework ドメイン層 (別 vendor package)
│   ├── src/
│   │   ├── Input/            #   Be Input 起点クラス
│   │   ├── Being/            #   Being (途中状態)
│   │   ├── Final/            #   Final (終点 state)
│   │   ├── Reason/           #   理由・サービス
│   │   └── Exception/        #   ドメイン例外
│   ├── tests/
│   └── composer.json         #   my-vendor/be-mart-be (path repo として参照)
├── tests/                    # BEAR 層のテスト
└── .claude/
    ├── workflows/            #   /run migrate のステップ JSON
    └── prompts/              #   各ステップの skill prompt
```

---

## 5. Phase B 進捗一覧 (HANDOVER の正式記録より抜粋)

| Slice | コミット | 内容 | 状態 |
|---|---|---|---|
| Slice 1 | `f81a2a5` | Psalm taint analysis scaffolding | 完了 |
| Slice 2 | (廃止) | (Slice 3 に統合) | — |
| Slice 3 | `9e4c46f` | env-gated ProdModule — PII log 防止 | 完了 |
| Slice 4 | `b002c7b` | mass-assignment fix for Pilot 5 F-2 | 完了 |
| Slice 5 | `c438664` | env-gated entry point (bin/app.php, public/index.php) | 完了 |
| Slice 6 | `51256d4` | AUTHZ ownership check for Pilot 5 F-1 | 完了 |
| Slice 7 | `8847636` | production Session adapter (EC-CUBE bridge) | 完了 (BEAR 側のみ) |
| Slice 7.1 | `0c48d8b` | rename + `@` 除去 + HANDOVER 修正 | 完了 |
| Slice 8 | `91198e6` | CSRF token (BEAR 側のみ) | 完了 (BEAR 側のみ) |
| Slice 9 | `ed0e9b1` | Taint annotation (Pilot 1-5 全体) | 完了 (※ opacity 課題あり) |
| Pilot 6 | `f34bd78` | doLogin — Direct (credentials verification) | 完了 |
| Pilot 7 | `27e8ee9` | doActivateCustomer — Direct (idempotent activation) | 完了 |
| Pilot 8 | `2039e43` | doUpdateCustomer — Direct (AUTHZ + partial merge) | 完了 |
| Pilot 9 | `111000b` | goCart — Direct (safe read, multi-cart aggregation) | 完了 |
| Pilot 10 | `a9f45ee` | doUpdateCartItemQuantity — Linear (G-17 chain-class-fixed) | 完了 |
| Pilot 11 | `a9f45ee` | doRemoveCartItem — Direct | 完了 |
| Pilot 12 | — | doReorder — Diamond-Cascade (OrderItem 拡張要、deferred) | 未着手 |
| Pilot 13 | `26a1591` | doAddFavorite — Direct (idempotent + AUTHZ) | 完了 |
| Pilot 14 | `8709bbe` | doRequestPasswordReset — Direct (anti-enumeration) | 完了 |
| Pilot 15 | `3e821e0` | doSubmitContact — Direct (anonymous + dual mail) | 完了 |
| Wave 1A | `3028041` | Pilot 12 prep (OrderItemEntity infrastructure) | 完了 |
| Wave 1B | `c366723` | doRemoveFavorite — Direct (Pilot 13 inverse) | 完了 |
| Wave 1C | `87e0319` | doResetPassword — Direct (Pilot 14 consumer, single-use) | 完了 |
| Pilot 12 | `263c525` | doReorder — Diamond-Cascade | 完了 |
| Wave 2E | `351dcd1` | doLogout — Direct | 完了 |
| Wave 2F | `cef5447` | goMypage — Direct safe-read aggregation | 完了 |
| Wave 2G | `ab2e674` | doWithdrawCustomer — Direct + multi-side-effect | 完了 |
| Wave 3H | `6dba995` | 4 go* form renderers (Login/Entry/Contact/MypageWithdraw) — pure BEAR | 完了 |
| Wave 3I | `e6ac521` | goMypageHistory + goMypageChange — Direct authenticated | 完了 |
| Wave 3J | `ac1ce6f` | goShopping — Direct aggregation | 完了 |
| Wave 4K | `b925397` | admin AAA infra + doAdminLogin + doAdminLogout | 完了 (※ ALPS 後追記済) |
| Wave 5M | `31e1d93` | goCustomerList — Direct + admin AUTHZ + filter | 完了 |
| Wave 5N | `1e22b42` | goCustomer — Direct + admin AUTHZ + aggregation | 完了 |
| Wave 5O | `0bb3ea0` | doCreateCustomer — Multi-Reason Being + admin AUTHZ | 完了 |
| Wave 6P | `2e7184b` | customer address book (4 transition) — Direct + AUTHZ | 完了 |
| Wave 6Q | `bb9a328` | goFavoriteList — Direct safe-read | 完了 |
| Wave 6R | `0f0ffe1` | goOrderHistory — Direct + pagination | 完了 |
| Wave 6S | `b071142` | doDeleteCustomer — Direct + admin soft-delete | 完了 |
| Wave 7X | `2241185` | SKILL bake (G-14 〜 G-22 as docs/skills/) — docs only | 完了 |
| Wave 7Y | `a59485a` | admin order management (4 transition) — Direct + admin AUTHZ | 完了 |
| Wave 7W | `f326e12` | guest checkout entry (2 transition) — Direct stub | 完了 |
| Slice 10 | — | 存在オラクル軽減 (404/403 統一) | 未着手 (要判断) |
| Slice 11 | — | Be Framework Psalm plugin (opacity 対策) | 未着手 (要判断) |

### Slice 7 / 8 の共通の積み残し

**本番経路の AUTHZ と CSRF はまだ実用にならない**。`EccubeSharedSessionAdapter`
は `$_SESSION['customer_id']` を、`EccubeSharedCsrfTokenAdapter` は
`$_SESSION['_csrf_token']` を読むだけで、それらを書く EC-CUBE 側 EventListener
が無いと全 HTTP リクエストが「anonymous かつ no token」 → 全 403 になる。
「fail-closed で安全側」 ではあるが「auth/csrf が動いている」 とは言えない。

詳細は HANDOVER の "Phase B — Slice 7" / "Slice 8" セクション → "EC-CUBE 側
contract" を参照。実装が必要なのは以下の 1 つの Symfony EventListener で、
`customer_id` と `_csrf_token` の両方を mirror する形にまとめる:

```php
// app/Customize/EventListener/BeMartSessionMirrorListener.php (EC-CUBE 側)
public function onLoginSuccess(InteractiveLoginEvent $event): void
{
    $customer = $event->getAuthenticationToken()->getUser();
    $session = $event->getRequest()->getSession();
    if ($customer instanceof \Eccube\Entity\Customer) {
        $session->set('customer_id', (string) $customer->getId());
    }
    // Slice 8 mirror: 状態変更フォームが見る intention id を 1 つ固定して
    // _csrf_token にコピーする。
    $session->set('_csrf_token', $this->csrfManager->getToken('bemart_form')->getValue());
}

public function onLogout(LogoutEvent $event): void
{
    $session = $event->getRequest()->getSession();
    $session->remove('customer_id');
    $session->remove('_csrf_token');
}
```

これを Slice 7.2 / 8.2 (統合 mirror) として BEAR リポジトリで直接書く
(EC-CUBE 移植の練習) か、Phase 2 (EC-CUBE 移植) に送るかは判断点。

---

## 6. よく使うコマンド

### ALPS

```bash
asd --lint alps.json        # バリデーション
asd -e alps.json            # HTML 再生成 (docs/ への同期は手動)
asd -s alps.json            # SVG 状態遷移図
```

### 移植ワークフロー

```text
/run migrate <descriptor-id>
```

例: `/run migrate Product`, `/run migrate AddCartItemInput`

### テスト・型・taint

```bash
composer test
composer psalm
composer psalm-taint
```

### bin/app.php (CLI 経由でリソース叩く)

```bash
# dev (app context) — FakeCsrfToken::TOKEN を csrfToken に渡す
APP_CONTEXT=app php bin/app.php page://self/shopping/checkout \
  '{"preOrderId":"aaaa00000000000000000000000000000000aaaa","csrfToken":"fake-csrf-token-bemart-2026"}'

# prod (CLI には BEMART_CLI_CUSTOMER_ID + BEMART_CLI_CSRF_TOKEN が必要)
APP_CONTEXT=prod \
  BEMART_CLI_CUSTOMER_ID=customer-001 \
  BEMART_CLI_CSRF_TOKEN=cli-smoke-token \
  php bin/app.php page://self/shopping/checkout \
  '{"preOrderId":"aaaa00000000000000000000000000000000aaaa","csrfToken":"cli-smoke-token"}'
```

注: `bin/app.php` は 4xx → exit 1、5xx → exit 2 で動く (Slice 5 で決定)。
CSRF token 不一致 or 未指定は 403 (Slice 8 で追加)。

---

## 7. 移行で持ち越せないもの (= 新マシンで再構築が必要)

- **Claude Code のセッション履歴** — `~/.claude/projects/` 配下にローカル保存
  されており、別マシンに引き継ぐ手段はない。HANDOVER 経由で文脈再ロード する
  のが唯一の正解。
- **`~/.claude/CLAUDE.md`** (個人 global 規約) — 別マシンで個別セットアップ。
- **インストール済 skill** (`be-framework-skills`, `alps-skills`, `bear-skills`
  など) — `claude plugin install` などで個別に。プロジェクト側 `.claude/prompts/`
  からこれらの skill 名を呼んでいるので、欠けていると `/run migrate` が動かない。
- **PHPStorm / IDE 設定** — 別マシン側で。

リポジトリ内 `.claude/` (workflows, prompts) は git 管理されているので自動で
付いてくる。

---

## 8. トラブルシュート

### `composer install` が失敗する

`be/` を path repository として参照している。`composer.json` の `repositories`
セクションを確認。`be/composer.json` が存在することと、`be/` の中で
`composer install` も必要 (vendor を 2 箇所に作る)。

### `composer test` で `var/log/bemart.json` 残り問題

dev (app) context は意図的にログを書く。`tests/EntryPoint/AppEntryPointTest.php`
の setUp/tearDown でクリーンアップしている。手動消去するなら:

```bash
rm -f var/log/bemart.json
```

### `composer psalm-taint` で false positive

Slice 1 で導入した `$_GET/$_POST/$_SESSION` source → `gateway::charge` 等
sink の taint flow 検査。新たに `@psalm-taint-sink` 付き API を追加した時に
graph が切れる可能性。`psalm.xml` の `taintAnalysis` セクションを参照。

---

## 9. 1 行サマリ (これだけで十分なケース)

```text
git clone https://github.com/koriym/ec-cube-alps.git && cd ec-cube-alps
&& git checkout be-first-migration-bootstrap && composer install
&& composer test && claude
# あとは HANDOVER.md を読ませて Slice 10 / 11 / EC-CUBE listener どれに進むか相談
```
