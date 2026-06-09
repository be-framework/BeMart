---
layout: default
title: "AI独自実装の反省と標準回帰"
---

# AI独自実装の反省と標準回帰

BeMart は ALPS / Be Framework / BEAR.Sunday / Ray.MediaQuery / Twig HTML を横断する大規模な実証アプリである。規模が大きいほど、AI エージェントは「今すぐ通す」ためにフレームワーク境界へ独自実装を足しやすい。本書はその失敗を隠さず、再発防止のために何が起きたかを記録する。

## 結論

失敗の中心は、業務コードではなく **BEAR.Sunday の境界コード** にあった。

- Bootstrap を transfer shell ではなく例外処理・HTTP emit・download/redirect調整の集積所にした。
- Injector / context 合成を `BEAR\Package\Injector` に委譲せず、手書き map で解決した。
- Router に EC-CUBE route 名、underscore URL、param alias、業務互換を吸収させた。
- Twig に `url()` / `path()` route generator を残し、Resource path と画面URLの二重帳簿を作った。
- MediaQuery interface を手書き一覧にし、新規Query追加時に module 編集を要求した。
- CSRF のために Resource invocation 境界を横取りし、Resource引数の明示契約を曖昧にした。
- 補助生成器やWeb E2E証跡を、最初は「成果物として残すべきもの」と「作業用の道具」に分けきれなかった。

いずれも「標準を調べずに局所解を作った」ことが原因である。

## 何をどれだけ失敗したか

| 領域 | 典型的な独自実装 | なぜ作ったか | 問題化したこと | 標準回帰/整理 |
|---|---|---|---|---|
| Router | Aura.Router route map、EC-CUBE route名、underscore alias、param map | EC-CUBE由来テンプレートを早く動かすため | `/admin_product_csv` 404、`/admin/order` が詳細Resourceへ誤解決、URLとResource pathの二重帳簿 | #56 で Web Router を全廃し、HTTP URL = BEAR Resource path へ寄せた |
| Bootstrap | 423行のHTTP制御、`header()`、`http_response_code()`、`ResourceObject::toString()` | redirect/download/例外をその場で補正するため | BEAR の transfer/responder 境界が見えなくなり、標準比較が困難 | #76 で小さな BEAR transfer shell へ回帰 |
| Injector / context | 手書き context-to-module map、`Ray\Di\Injector` 直接構築 | `html-prod-hal-api-app` 等の複合contextを早く作るため | `BEAR\Package\Injector` の合成規約から外れ、wrapper moduleが増えた | #76 で `BEAR\Package\Injector` 委譲へ回帰 |
| Module wrapper | `HtmlProdModule` / `HtmlTestModule` / `HalApiModule` 等 | HTML/API、prod/test/fakeを組み合わせるため | AppModule重複installやcontext命名の揺れが起きやすい | #76 で削除し、BEAR.Package の token composition へ置換 |
| MediaQuery | `MediaQueryRuntimeModule::queryClasses()` の手書き一覧 | SQL移行中に確実に登録するため | Query追加のたびにmodule編集が必要になり、標準のdiscoverabilityを失った | #76 で `MediaQueryQueries::fromAppRoot()` による discovery へ整理 |
| CSRF/session | `RequestQueryCapturingInvoker` / `RequestQueryContext` | interceptorから入力tokenを読むため | Resource invocation 境界の隠れ状態に依存 | #76 で request capture を削除し、interceptor が `ResourceObject->uri->query` を読む形へ整理 |
| JSON Schema | 巨大な補助生成器、schema品質ゲート、作業用台帳 | Semantic-Ex品質を上げるため | 最初は生成器をPR成果物に含めすぎ、schema資産と作業道具が混ざった | #55 で schema/docs資産へ整理。補助生成器はPR成果物から除外 |
| Web E2E証跡 | 大量スクリーンショット、重複画像、途中run混在 | 全機能ブラウザ検証を証明するため | 証拠量が増えすぎ、重複・誤リンク・pass/fail矛盾がレビュー負債化 | #57 で最終runへ一本化、重複削除、誤証跡を訂正 |

## PR履歴から見える流れ

| PR/commit | 学び |
|---|---|
| #11 HTTP hypermedia / router 導入 | Webを動かすためにrouter層へ機能を足し始めた。ここで標準境界の参照を徹底すべきだった。 |
| #12 MediaQuery follow-up | SQL境界は Ray.MediaQuery interface + SQL file へ寄せる方針を得た一方、runtime moduleの手書き一覧が残った。 |
| #17 Aura.Router | 互換routeを薄く見せたが、実際には業務aliasとURL生成を吸収し、後の全廃コストを生んだ。 |
| #53 Resource exception CI | 例外/HTTP応答の扱いをBootstrap側へ集める方向に進み、Bootstrap肥大化が固定化した。 |
| #55 Semantic-Ex JSON Schema | 「通るschema」から「意味ある契約」へ改善できたが、生成器や作業用スクリプトを成果物に混ぜる危険も露出した。 |
| #56 Web Router全廃 | 互換性より単純性を選び、HTTP URL = Resource path に戻した。独自routerを作らないことの価値が明確になった。 |
| #57 Web E2E証跡整理 | 証拠は必要だが、重複・途中run・誤リンクは資産ではなくノイズになると分かった。 |

## なぜAIは独自実装したか

1. **局所的なgreenを優先した**  
   テストを通す対象が狭いと、BootstrapやRouterで吸収するのが最短に見える。

2. **参照アプリを見ずに推測した**  
   BEAR.Skeleton / MyVendor.Cms / `vendor/bear/package` を先に読む手順が固定されていなかった。

3. **境界コードと業務コードを同じ重さで扱った**  
   ResourceやSQLの局所変更と、Bootstrap/Injector/Routerの変更は影響範囲が違う。後者はADR級で扱うべきだった。

4. **互換性をrouterに押し込んだ**  
   `id` → `productCode`、EC-CUBE route名 → Resource path、underscore URL → canonical URL のような変換をrouterが抱えると、後で全画面に波及する。

5. **成果物と作業道具を混同した**  
   schema生成器、Web E2E画像、途中run JSONは、レビュー対象として残すものと作業中だけ使うものを分ける必要があった。

## 現在の標準逸脱監査

PR #76 時点で `python3 scripts/bear_standard_audit.py . --json` は `count: 0` である。

主な解消内容は次の通り。

| 領域 | 状態 |
|---|---|
| Bootstrap | 小さな BEAR transfer shell へ回帰。 |
| Injector / context | `BEAR\Package\Injector` 委譲へ回帰。 |
| Module wrapper | 旧 wrapper module を削除し、BEAR.Package token composition へ置換。 |
| MediaQuery | 手書き class list をやめ、`MediaQueryQueries::fromAppRoot()` へ整理。 |
| CSRF request capture | `RequestQueryCapturingInvoker` / `RequestQueryContext` を削除。 |

監査が green でも、`CanonicalResourceRouter` や `DownloadResponder` のような互換層は残っている。これらは業務互換や streaming 化と同時に、別 PR で小さく削る。

## 再発防止のルール

- Bootstrap / Injector / Router / Transfer / Module context / MediaQuery / CSRF / JsonSchemaModule / entrypoint に触る前に [`standard-first-rule.md`](standard-first-rule.md) を読む。
- 参照順は BEAR.Skeleton → MyVendor.Cms → `vendor/bear/package` → 対象プロジェクト既存実装。
- 独自境界コードは、標準で代替不能な理由をPR本文またはADRに書くまで実装しない。
- 互換aliasはrouterで吸収しない。Resource pathとHTML form/query名を正にする。
- 成果物、証跡、作業道具を分ける。レビューに残すものだけをcommitする。

## 次に解消する順序

1. `CanonicalResourceRouter` を不要にするため、template / form 側の legacy compatibility を削る。
2. `DownloadResponder` を CSV/PDF/ZIP の標準 streaming へ寄せる。
3. ActionRedirect 互換層を Resource / schema / smoke fixture / apidoc と同時に整理する。
4. `bear/devtools` の workflow-test contract がタグリリースされたら、dev branch 依存をタグへ戻す。
5. Web E2Eを再実行し、標準回帰で壊れていないことを証明する。
