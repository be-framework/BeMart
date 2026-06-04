# ALPS Linked Documentation

`alps-doc/` は、`alps.json` の descriptor から `doc.href` で参照する長文補足の置き場である。

ALPS の `doc.value` は、descriptor を読むための短い要約に留める。意味論、境界判断、SQL / HTML / Resource との対応、実装上の根拠が長くなる場合は、無理に `doc.value` へ詰め込まず、このディレクトリ配下の Markdown へ分離する。

## Rule

- `doc.value` には、その descriptor が何を意味するかを短く書く。
- `doc.href` には、追加説明が必要なときだけ Markdown への相対リンクを書く。
- Markdown 側では、背景、制約、実装根拠、残差、検証方法を書いてよい。
- `doc.href` 先は ALPS の意味を補足するものであり、descriptor の意味を別定義するものではない。

## Example

```json
{
  "id": "AdminOrderEditPage",
  "title": "管理受注編集画面",
  "doc": {
    "href": "alps-doc/admin-order-edit.md",
    "value": "管理者が受注を確認・編集し、配送・通知・帳票導線へ進む画面状態。"
  }
}
```

既存の `docs/states/*.md` は、画面状態の補足として同じ方針で使われている。今後、新しい ALPS descriptor から直接参照する長文補足は、原則として `alps-doc/*.md` に置く。
