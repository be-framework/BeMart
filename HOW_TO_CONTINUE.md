# HOW_TO_CONTINUE.md

別マシンでこの BeMart プロジェクトの作業を再開するための完全ガイド。
最終更新: 2026-05-19 (Slice 7.1 完了直後)

---

## 0. 現状サマリ

- **ブランチ**: `be-first-migration-bootstrap`
- **リモート**: `https://github.com/koriym/ec-cube-alps.git`
- **最終コミット**: `0c48d8b` — Phase B Slice 7.1: rename SymfonySessionAdapter -> EccubeSharedSessionAdapter
- **テスト**: 73 tests, 181 assertions すべて green
- **Psalm / Psalm-taint**: errors なし
- **未 push のローカルコミット**: なし (origin と一致)

進捗の正は `HANDOVER.md` (1100 行近く)。Phase A の Pilot 1-5 + Phase B の
Slice 1-7.1 の全 decision log と積み残しが入っている。新マシン側 Claude が
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

直近のコミット 0c48d8b (Phase B Slice 7.1) まで完了しています。次は以下の
どちらかから選びたい:

  A. Slice 8: CSRF token (全 onPost に CSRF guard)
  B. EC-CUBE 側 EventListener (Slice 7 の積み残し: $_SESSION['customer_id']
     ミラー実装。これが入って初めて本番経路で AUTHZ が実際に customerId を
     検証できる)

どちらを推奨するか、判断基準を出してください。
```

判断基準は HANDOVER の Slice 7 セクション 末尾 (「次の Slice (Slice 8 以降)」
表) にも書いてあるが、改めて出してもらう方が再ロード後の整合確認になる。

---

## 3. 必読ファイル (Claude が最初に読む順)

1. **`HANDOVER.md`** — 構築過程の全記録。Pilot 1-5 (Phase A) と
   Slice 1-7.1 (Phase B) の decision log・積み残し・トレードオフ。
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
│   ├── Auth/                 #   Session adapter (Slice 7)
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
| Slice 8 | — | CSRF token | **未着手** |
| Slice 9 | — | Taint annotation 拡張 | 未着手 |
| Slice 10 | — | 存在オラクル軽減 (404/403 統一) | 未着手 (要判断) |

### Slice 7 の重要な積み残し

**本番経路の AUTHZ はまだ実用にならない**。`EccubeSharedSessionAdapter` は
`$_SESSION['customer_id']` を読むだけで、それを書く EC-CUBE 側 EventListener
が無いと全 HTTP リクエストが anonymous → AUTHZ 全 403 になる。「fail-closed
で安全側」 ではあるが「auth が動いている」 とは言えない。

詳細は HANDOVER の "Phase B — Slice 7" セクション → "EC-CUBE 側 contract"
を参照。実装が必要なのは以下の 5 行程度の Symfony EventListener:

```php
// app/Customize/EventListener/SessionMirrorListener.php (EC-CUBE 側)
public function onLoginSuccess(InteractiveLoginEvent $event): void
{
    $customer = $event->getAuthenticationToken()->getUser();
    if ($customer instanceof \Eccube\Entity\Customer) {
        $event->getRequest()->getSession()->set('customer_id', (string) $customer->getId());
    }
}

public function onLogout(LogoutEvent $event): void
{
    $event->getRequest()->getSession()->remove('customer_id');
}
```

これを Slice 7.2 として BEAR リポジトリで直接書く (EC-CUBE 移植の練習) か、
Phase 2 (EC-CUBE 移植) に送るかは判断点。

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
# dev (app context)
APP_CONTEXT=app php bin/app.php page://self/shopping/checkout \
  '{"preOrderId":"aaaa00000000000000000000000000000000aaaa"}'

# prod (CLI には BEMART_CLI_CUSTOMER_ID が必要 — 設定なしは anonymous → 403)
APP_CONTEXT=prod BEMART_CLI_CUSTOMER_ID=customer-001 \
  php bin/app.php page://self/shopping/checkout \
  '{"preOrderId":"aaaa00000000000000000000000000000000aaaa"}'
```

注: `bin/app.php` は 4xx → exit 1、5xx → exit 2 で動く (Slice 5 で決定)。

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
# あとは HANDOVER.md を読ませて Slice 8 / EC-CUBE listener どちらに進むか相談
```
