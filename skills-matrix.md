# Skills Matrix

## 目的

EC-CUBE を BEAR.Sunday + Be Framework へ移植する際に、Codex 上で何の skill を使うかを固定する。ここでは「あると便利」ではなく、「いつ使うかが明確なもの」だけを残す。

## 必須

- `planning-with-files`
  - planning files を維持し、context 0% 復帰点を固定する
- `be-semantic`
  - Story / glossary / input 制約から Semantic を先に固める
- `bear-from-alps`
  - `alps.json` から resource packet の叩き台を起こす
- `bear-review`
  - BEAR.Sunday の resource 規約と packet 出力を確認する
- `bear-smoke-test`
  - packet 完了時の最小回帰確認を行う

## 推奨

- `be`
  - Semantic から Be の Input / Final / Reason へ落とす
- `semantic-ex`
  - source/ALPS だけでは制約が読み切れないときに使う
  - 今回の移植では補助的
- `bear-hypermedia`
  - `#[Link]` や遷移関係を補完する

## 任意

- `bear-to-alps`
  - 実装で得た知見を ALPS へ戻したいときに使う
- `malt` (`koriym/homebrew-malt`)
  - upstream README では `Claude Code Skill` として案内されている
  - plugin marketplace 経由で導入できる
  - macOS + Homebrew 前提で、PHP / MySQL / Nginx / Redis を native に立ち上げたいときに使う
  - 移植先 repo の Day 0 環境構築には有効だが、この artifact repo 自体には不要
  - 導入例:
    - `/plugin marketplace add koriym/homebrew-malt`
    - `/plugin install malt@koriym-malt`
- `github:gh-fix-ci`
  - 実移植 repo で CI を直す段階に入ったら使う
- `github:yeet`
  - PR 作成まで自動化したい段階で使う

## 不要

- `kbits`
  - 今回の Codex 運用には不要
- Google Drive 系 skill
  - 現時点の移植フローには不要

## フェーズ対応

1. packet 設計
   - `planning-with-files`
   - `be-semantic`
2. Be semantic 固定
   - `be`
   - 必要なら `semantic-ex`
3. resource 叩き台生成
   - `bear-from-alps`
   - 必要なら `bear-hypermedia`
   - ローカル実行環境が未整備なら必要に応じて `malt`
4. review
   - `bear-review`
5. packet close
   - `bear-smoke-test`

## 現在の最小セット

今の `resource-contract` packet と `Quantity` / `AddCartItemInput` の `be-semantic` packet を回すだけなら、実質的な最小セットは次の5つ。

- `planning-with-files`
- `be-semantic`
- `bear-from-alps`
- `bear-review`
- `bear-smoke-test`
