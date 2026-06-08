---
layout: default
title: "Standard First Rule"
---

# Standard First Rule

BeMart で BEAR.Sunday のフレームワーク境界を触る前に読むルールである。目的は、AI エージェントが「便利そうな独自実装」を作り、後で標準回帰に大きなコストを払うことを防ぐことにある。

## 対象

以下に触るPRは、この文書を適用する。

- `src/Bootstrap.php`
- `src/Injector.php`
- `src/Module/*Module.php` の context composition
- `src/Support/*Router*` / `RouterInterface` 実装
- Transfer / Responder / public entrypoint
- MediaQuery wiring
- JsonSchemaModule installation / schema runtime validation
- CSRF / session / Resource invocation boundary
- Twig の URL生成・form action・method override

## 標準参照順

実装前に、必ずこの順で見る。

1. **BEAR.Skeleton**
   - `src/Bootstrap.php`
   - `src/Injector.php`
   - `src/Module/AppModule.php`
2. **MyVendor.Cms**
   - `src/Bootstrap.php`
   - `src/Injector.php`
   - `src/Module/AppModule.php`
   - `docs/conventions.md`
3. **対象プロジェクトの vendor**
   - `vendor/bear/package/src/Injector.php`
   - `vendor/bear/package/src/Module.php`
   - `vendor/bear/package/src/Context/*.php`
4. **BeMartの既存実装**
   - 既存実装は最後に見る。既存が独自実装なら、それを前例にしない。

## 実装前チェックリスト

| 質問 | Yesなら |
|---|---|
| BootstrapにHTTP header/status/body emitを追加しようとしているか | 標準Transfer/Responderでできない理由を先に書く |
| Routerでroute名、legacy URL、param alias、業務互換を吸収しようとしているか | やめる。HTML/form/queryをResource引数名へ寄せる |
| Injectorでcontext文字列を手書きmatchしようとしているか | `BEAR\Package\Injector` の合成規約を調べる |
| Module wrapperを追加しようとしているか | context atomの組み合わせで表せない理由を書く |
| MediaQuery interface一覧を手書きしようとしているか | directory discoveryを検討する |
| CSRF/sessionのためにInvokerやResource呼び出し境界を横取りしようとしているか | Resource引数として明示できない理由を書く |
| Twigに `url()` / `path()` generatorを戻そうとしているか | やめる。canonical Resource pathを使う |
| JsonSchemaの逃げ型でテストだけ通そうとしているか | ALPS/Fake/実レスポンス観察へ戻る |

## 赤信号

次の状態になったら実装を止め、PRを小さく切り直す。

- Bootstrap が小さな transfer shell ではなくなる。
- `src/Injector.php` が `BEAR\Package\Injector` に委譲していない。
- Router が業務語彙や互換URLを知っている。
- `header()` / `http_response_code()` / `ResourceObject::toString()` をBootstrapが直接呼ぶ。
- MediaQuery interface が手書き配列で列挙される。
- Resource invocation boundaryでrequest query/bodyを捕捉する。
- TemplateがEC-CUBE route名や `url()` / `path()` に戻る。

## 独自実装を許す条件

独自境界コードは原則禁止。ただし次のすべてを満たす場合だけ許可する。

1. 標準BEAR実装では解けない。
2. BEAR.Skeleton と MyVendor.Cms に適切な例がない。
3. `vendor/bear/package` の意図に反しない。
4. PR本文またはADRに、なぜ必要か・なぜ小さいか・いつ消すかを書く。
5. 監査コマンドで検出可能にする。

## 監査

境界PRの前後で以下を実行する。

```bash
python3 ~/.codex/skills/bear-standard-guard/scripts/bear_standard_audit.py .
```

期待値:

- 新規の赤信号を増やさない。
- 既存の赤信号を解消するPRでは、対象IDの件数を減らす。
- どうしても残すものは、PR本文または台帳に理由を書く。

## PR本文に書くこと

境界PRでは、少なくとも次を書く。

- 参照した標準実装: BEAR.Skeleton / MyVendor.Cms / vendor のどれを見たか。
- 独自実装を追加したか。追加したなら理由。
- `bear_standard_audit.py` のbefore/after。
- 実行したテスト。

## BeMartでの現在の適用

2026-06-08時点のBeMartには、Bootstrap / Injector / context wrapper / MediaQuery手書き一覧 / CSRF request capture が残っている。これらは「許可済みの標準」ではなく、次PR群で削る対象である。
