---
layout: default
title: "Architecture Audit Baseline"
---

# Architecture Audit Baseline

BeMart の BEAR.Sunday 境界を標準へ戻すための監査baselineである。目的は、独自実装を「なんとなく気持ち悪いもの」ではなく、再現可能な検出項目として扱うことにある。

## コマンド

```bash
composer architecture:audit
```

このコマンドは `scripts/bear_standard_audit.py` を実行し、Bootstrap / Injector / Router / Module context / MediaQuery / Resource invocation / Twig route generator の標準逸脱を検出する。

## 現在の期待値

2026-06-08時点では **greenではない**。これは意図した状態である。現在残っている独自実装をfailとして可視化し、後続PRで1カテゴリずつ減らす。

| ID | Severity | 件数 | 対象 | 次の対応 |
|---|---|---:|---|---|
| `bootstrap.too_large` | error | 1 | `src/Bootstrap.php` | Bootstrap標準化PRで小さなtransfer shellへ戻す |
| `bootstrap.manual_transfer` | error | 3 | `src/Bootstrap.php` | `header()` / `http_response_code()` / `toString()` 直接処理をTransfer/Responderへ戻す |
| `injector.custom_context_map` | error | 1 | `src/Injector.php` | `BEAR\Package\Injector` へ委譲する |
| `injector.manual_context_match` | error | 1 | `src/Injector.php` | 手書きcontext mapをBEAR.Packageの合成規約へ戻す |
| `injector.direct_ray_injector` | warning | 1 | `src/Injector.php` | 直接 `Ray\Di\Injector` 構築をやめる |
| `module.context_wrapper` | warning | 7 | `src/Module/*Module.php` | wrapper moduleをcontext atomへ再整理する |
| `media_query.manual_class_list` | error | 1 | `src/Module/MediaQueryRuntimeModule.php` | Query discoveryへ移行する |
| `resource.request_capture` | warning | 2 | `src/Support/Resource/*` | CSRF tokenをResource引数から読む |

合計: 17件。

## 使い方

- 標準回帰PRの前に `composer architecture:audit` を実行し、対象IDのbeforeを確認する。
- PRで解消したカテゴリについて、件数が減っていることを確認する。
- 新規のIDを増やしてはいけない。
- 独自実装を残す場合は、PR本文またはADRに理由を書く。

## このPRでやらないこと

このbaseline追加PRでは、Bootstrap / Injector / MediaQuery / CSRF の実装修正は行わない。監査入口を作り、後続PRの評価軸を固定するだけである。
