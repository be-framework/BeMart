---
layout: default
title: "HTML Link Audit — 理由と分類"
---

# HTML Link Audit — 理由と分類

Page リソースが `#[Link]` で宣言した遷移が、描画された HTML の中に affordance
（`<a>` / `<area>` / `<link>` / `<form>`）として存在するかを BEAR.Dev の
`HtmlLinkAuditor` が検査する。結果は `tests/Html/html-link-audit-ledger.json`
（台帳）と突き合わせ、`tests/Html/HtmlLinkAuditLedgerTest` が判定する。

- 台帳にない warning が出たら fail（未分類）
- 台帳にあるのに出なくなった warning も fail（台帳が古い）
- 本番・開発の HTML コンテキストでは warning は出力しない（`SilentHtmlLinkAuditLogger`）。判定は台帳テストだけが持つ

## reason — auditor が返す理由

| reason | 意味 |
|---|---|
| `target-missing` | `href` のパスに一致する affordance が HTML にない |
| `method-mismatch` | パスは一致するが method が一致しない。form は `method` 属性、hidden `_method`、action の `?_method=` の順で判定する |
| `semantic-token-missing` | パスも method も一致するが、`rel` / `class` に遷移 ID（`#[Link]` の `rel`）が無い |
| `html-missing` | view が空。HTML を返さないリソース |

## classification — 台帳での扱い

| classification | 意味 | note |
|---|---|---|
| `resourceOnly` | Resource 契約はあるが Web UI が未実装。追跡対象 | 任意 |
| `fail` | Web UI として実装されているべきだが affordance が無い、または壊れている | 任意 |
| `targetOut` | Web 操作検証の対象外（外部決済、実メール送信、本番ファイル破壊など） | 必須 |

`webImplemented` は台帳に書かない。warning が出ないことがその証明である。

## 台帳の形

```json
{
  "GET page://self/admin/product doDeleteProduct": {
    "reason": "target-missing",
    "classification": "resourceOnly",
    "note": "商品詳細に削除 affordance が無い"
  }
}
```

キーは `<GET page URI> <rel>`。対象ページと引数は
`tests/Smoke/ResourceSmokeTest::resourceProvider()` の `GET page://self/*`
（期待コード 200）を使う。HTML を持たないリソース（redirect shell、CSV
ダウンロード）は監査しない。

## 直し方

- `semantic-token-missing` — テンプレートの `<a>` / `<form>` に `rel="<rel>"` を付ける。UI は既にある
- `method-mismatch` — form の `_method` を `#[Link]` の method に合わせる。片方が間違っている
- `target-missing` — UI を実装するか、`resourceOnly` / `targetOut` として台帳に残す

直した warning は台帳から消す。消し忘れは台帳テストが `stale` として検出する。
