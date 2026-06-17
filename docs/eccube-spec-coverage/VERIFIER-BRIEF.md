# EC-CUBE 結合試験項目書 × BeMart カバレッジ — 検証ブリーフ（verifier 用）

このファイルは **検証モデル（Opus 推奨。判定の堅さ・低ハルシネーションが要る）** に渡す自己完結タスク。
実行者（DeepSeek 等）が [`EXECUTOR-BRIEF.md`](EXECUTOR-BRIEF.md) に従って採取した証拠を、**期待結果と突合して判定**し、再生成可能な matrix にする。

## 0. あなたの役割と鉄則（最重要）
あなたは **検証者（verifier）**。実行者の証拠（HTML/status/screenshot）と**期待結果（原文）だけ**で判定する。

- 鉄則1: **疑わしきは Covered にしない**（偽陽性が最悪の失敗）。証拠が期待結果を満たすと言い切れなければ Covered にしない。
- 鉄則2: 実行者の `observed` は参考。**鵜呑みにせず、証拠そのものを自分で読む**。
- 鉄則3: `Covered` には**機械確認できる恒久的裏付け**を必ず添える（`alps.json` の descriptor を grep した結果 / Resource ファイルパス / 既存テスト名）。一時的なスクショ1枚を Covered の根拠にしない。
- 鉄則4: 証拠が判定に不足なら `insufficient-evidence`（実行者へ差し戻し、追加で何を撮るべきか明記）。捏造で埋めない。
- 鉄則5: `N/A` は理由必須（EC-CUBE 固有でBeMart が意図的に移植しない領域）。
- 鉄則6: 集計（カバー率）は**最後**。Covered に Partial / insufficient を混ぜない。

> 背景: BeMart は過去に「タグ語彙の変更で 142 個の descriptor が射影から静かに脱落、人が偶然発見」という事故がある。**主張は機械確認する**こと。「動いている/記録済み」を継承しない。

## 1. 入力
- 実行者の出力: `docs/eccube-spec-coverage/records/<AREA>.jsonl` ＋ `evidence/<AREA>/*`
- 原典: `git clone --depth 1 -b 4.0 https://github.com/EC-CUBE/eccube-specification`（期待結果の原文照合用）
- BeMart 作業コピー（裏付け確認用。`alps.json`, `src/Resource/`, `be/src/`, `tests/`, `docs/eccube-feature-alps-status.html`, `docs/web-e2e/`）

## 2. 判定ルーブリック（`verifier_status`）
- **Covered**: 期待結果が証拠で満たされている **かつ** BeMart に恒久的裏付け（ALPS 遷移＋Resource、できれば既存テスト）がある。
- **Partial**: 画面/遷移は存在するが期待結果の一部のみ（例: 画面は出るがバリデーション文言・計算・状態遷移が違う）、または裏付けはあるが証拠が期待結果を完全には示さない。
- **Gap**: 501/404/未実装、または該当 ALPS/Resource が無い。
- **N/A**: EC-CUBE 固有でBeMart が意図的に移植しない領域（理由必須）。
- **insufficient-evidence**: 証拠が判定に足りない（差し戻し）。

判定の補助原則:
- `http_status` が 501/404/500 → 原則 **Gap**（500 は実装途中の可能性 → notes に明記）。
- スクショ/HTML が「画面は出たが期待結果の条件（エラー表示・金額・遷移先）を示していない」→ Covered にしない（Partial か insufficient）。
- fake コンテキストの証拠（`context != "sql"`）は **insufficient-evidence** 扱い（偽陽性源）。

## 3. 手順（1項目ごと）
1. 期待結果を**原典 md と照合**（実行者のコピーが正しいか）。
2. 証拠（`evidence/<AREA>/<ITEM_ID>.*`）を自分で読み、期待結果を満たすか判定。
3. `Covered`/`Partial` 候補は **BeMart 側の恒久裏付けを機械確認**:
   - `grep` で `alps.json` に該当 descriptor / transition があるか
   - 該当 Resource（`src/Resource/...`）・既存テストの実在
   - 結果を `backing` に列挙（無ければ Covered にしない → Partial か Gap）。
4. （任意・強く推奨）Covered の**層化サンプル**を自分で再実行し、証拠が現行 HEAD で再現するか確認（陳腐化・偽造の検出）。再実行のログイン・詰まりは [`EXECUTOR-BRIEF.md`](EXECUTOR-BRIEF.md) の §3（ログイン）/§7（トラブルシューティング）に従う。
5. レコードに `verifier_status` ＋ `rationale`（1–2文）＋ `backing` を付与。

## 4. 出力
```
docs/eccube-spec-coverage/
  verified/<AREA>.jsonl        # 実行者レコード + verifier_status / rationale / backing
  coverage-matrix.md           # 領域→件数→Covered/Partial/Gap/N-A/insufficient のカウント＋率＋ gap 一覧
```
verified JSONL の追加フィールド例:
```json
{"...executor fields...":"...","verifier_status":"Partial","rationale":"カート追加は再現するが、在庫切れ時のエラー文言が期待結果と不一致","backing":["alps.json#doAddCartItem","src/Resource/Page/Cart.php","tests/Resource/CartResourceTest.php"],"verified_at_commit":"<HEAD sha>"}
```
`coverage-matrix.md` には領域別カバー率と Gap 一覧を載せる。可能なら `bin/` に再生成スクリプトを追加して**一回こっきりにしない**（任意）。

## 5. スケール（任意・opt-in）
項目が数百規模なら、多エージェント workflow:
- 領域ごとに verify エージェント（fan-out）
- 各 `Covered` 候補に**敵対的セカンドオピニオン**（「この証拠は本当に期待結果を満たすか、反証せよ」）
- 多数決で Covered を確定 → 統合
コスト承認がある場合のみ。

## 6. 納品物
`verified/*.jsonl` ＋ `coverage-matrix.md`。サマリは領域別の Covered/Partial/Gap/N-A/insufficient 件数と率。
**EA（管理画面）は EF（フロント）より Gap が多い前提**で、正直に低く出してよい。
