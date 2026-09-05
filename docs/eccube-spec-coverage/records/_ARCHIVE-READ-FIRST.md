# ⚠️ ARCHIVE — この `records/` と `../evidence/` は「到達性のみ」の旧実行（検証に使わない）

このフォルダ（`records/*.jsonl`）と `../evidence/` は、最初の安価モデル実行による出力。
**各「手順」が実際には対象 URL の GET だけで、検索・登録・編集・削除・送信・多段遷移を一切操作していない。**
HTTP 200 が返った＝画面に到達できた、という**到達性(reachability)の記録**にすぎず、
**期待結果が起きたかは検証していない**。

- カバレッジ判定の証拠として**使わない**こと（GET 代用は鉄則0違反）。
- `verifier_status` / `executed` フィールドも欠落しており、[`../VERIFIER-BRIEF.md`](../VERIFIER-BRIEF.md) の入力要件を満たさない。

## 正となる実行（こちらを使う）
実ブラウザで**手順を実操作**し、項目ごとに スクショ(.png)＋DOM(.html)＋status を採取する方式に置き換えた:

- ハーネス: [`../harness/`](../harness/)（`run.cjs` / `scenarios.json` / `README.md`）
- 実行指示: [`../EXECUTOR-BRIEF.md`](../EXECUTOR-BRIEF.md)（ブラウザ方式に更新済み）
- 新しい証拠の出力先: `../evidence-browser/`（`browser-run.jsonl` ＋ `<AREA>-<ITEM_ID>.png/.html`）

到達性メモとして参照する以外、この `records/` / `evidence/` は破棄してよい。
