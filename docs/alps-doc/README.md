---
layout: default
title: "ALPS Linked Documentation"
---

# ALPS Linked Documentation

`docs/alps-doc/` は、`alps.json` の descriptor から `link rel="describedby"` で参照する長文補足の正本である。

ALPS の `doc.value` は、descriptor を読むための短い要約に留める。意味論、境界判断、SQL / HTML / Resource との対応、実装上の根拠が長くなる場合は、無理に `doc.value` へ詰め込まず、このディレクトリ配下の Markdown へ分離する。

## Rule

- `doc.value` には、その descriptor が何を意味するかを短く書く。
- 追加説明が必要なときは、descriptor 直下の `link` に `rel: "describedby"` と Markdown への `href` を書く。
- Markdown 側では、背景、制約、実装根拠、残差、検証方法を書いてよい。
- `link rel="describedby"` 先は ALPS の意味を補足するものであり、descriptor の意味を別定義するものではない。
- `describedby` のような公的な link relation はそのまま使う。独自 relation を使う場合は bare token にせず、`rel.alps.json#foo` のように relation の意味を辿れる `href` を使う。

## Example

```json
{
  "id": "AdminOrderEditPage",
  "title": "管理受注編集画面",
  "doc": {
    "value": "管理者が受注を確認・編集し、配送・通知・帳票導線へ進む画面状態。"
  },
  "link": [
    {
      "rel": "describedby",
      "href": "alps-doc/admin-order-edit.md"
    }
  ]
}
```

既存の `docs/states/*.md` は、画面状態の補足として同じ方針で使われている。今後、新しい ALPS descriptor から直接参照する長文補足は、原則として `docs/alps-doc/*.md` に置く。

GitHub Pages では `docs/` が publish root になるため、公開 HTML から辿る同名ファイルは `docs/alps-doc/` に置く。root の `alps-doc/` は `alps.json` の既存相対リンクを保つための `docs/alps-doc/` への互換 symlink であり、編集は `docs/alps-doc/` 側で行う。
