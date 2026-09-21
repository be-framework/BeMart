---
layout: default
title: "event-sourcing 観測ログ — credential redaction と replayable の契約"
---

# event-sourcing 観測ログ — credential redaction と replayable の契約

`bear/event-sourcing`(`ObserveModule`)経由で記録される観測ログ・抽出イベントストリームが、
BeMart の credential(password / secretKey / resetKey / authKey / deviceToken 等)を
どう扱うかの現在の契約をまとめる。構築の経緯・意思決定ログは
[`HANDOVER.md`](HANDOVER.md#2026-09-14--event-sourcing-観測ログ-credential-redaction-と-replayable-の契約)
を参照。ここは「今どうなっているか」の正。

依存: `bear/event-sourcing` `1.x-dev`(`composer.lock` は `8616c825`、
[bearsunday/BEAR.EventSourcing#22](https://github.com/bearsunday/BEAR.EventSourcing/pull/22)
のマージ commit)。タグ付きリリースはまだ無い(最新タグ `0.1.0` はマージ前)。

## redaction の仕組み(2系統、別々の層で効く)

- **request params**: ライブラリの `SensitiveParamsFilter` が `SemanticLogInvoker` に渡る
  params を redact する。**key は残り、値だけが `SensitiveParamsFilter::FILTERED`
  (`'[FILTERED]'`)に置き換わる**(Rails `filter_parameters` / Sentry scrubber と同じ流儀 —
  「credential が送られた」事実はログに残り、値だけが消える)。ネストした配列・オブジェクトも
  全深度で同じ扱い。標準の credential 部分文字列は `passw`/`pwd`/`passphrase`/`privatekey`/
  `token`/`secret`/`apikey`(大小文字と `_`/`-` を無視した部分一致)、transport 部分文字列は
  `csrf`。BeMart は `ObserveModule` で `new SensitiveParamsFilter(['resetKey', 'authKey'])`
  を `#[Filtered] ParamsFilterInterface` に束縛し、ライブラリ標準がわざと見ない
  `Key` サフィックス2件(`idempotencyKey` のような domain input を巻き込まないため)を
  追加している。アプリ独自のフィルタクラスは持たない。
- **response body**: `ExcludedResponseBodyStore`(`src/Module/ExcludedResponseBodyStore.php`)
  が `page://` スキームのレスポンス body を **一切保存しない**。フォームのプレフィル用に
  `resetKey`/`authKey`/`csrfToken` を body に含める `page://` レイヤー全体が対象
  (列挙型のブロックリストではなくスキーム境界で切る設計)。
  **`app://` の入れ子リソースの body は従来通り保存される** — response body 全体が
  redact 対象というわけではない。

この2系統は独立: request params の redaction は `page://`/`app://` を問わず効くが、
response body の除外は `page://` にしか効かない。

## `#[SensitiveParameter]` の付与範囲(PHP 例外スタックトレース対策)

`SensitiveParamsFilter`/`ExcludedResponseBodyStore` は観測ログを守るが、PHP 自身が例外の
スタックトレースに引数値を埋め込む経路は別問題。これは `#[SensitiveParameter]` で守る。

`#[SensitiveParameter]` が守るのは、その引数が宣言されているフレーム **1つ**の
trace 表示のみ(呼び出しチェーンの他フレームや、同じ値を別途保持するオブジェクト/配列
までは守らない)。効く経路は「credential を受け取った `onPost`/`onPut` 自身のフレームが
スタック上にある間に、その内側で例外が発生・伝播する」場合
(例: `Login::onPost` は browser-form でない経路で `SemanticVariableException` を
re-throw する。`$this->becoming(...)` 呼び出し中に Being/Final から例外が投げられる
場合も同様)。**`#[Ray\Csrf\Attribute\CsrfToken]` インターセプタは検証失敗時に例外を投げず 403 body を
セットして return するだけなので、この経路の対象ではない**(`MyVendor\BeMart\Interceptor\CsrfForbiddenInterceptor::invoke()`
で確認済み)。

- **Resource 層**: 名前が `/password|secret|token|resetKey|authKey/i` にマッチする
  `on*` メソッド引数すべてに付与済み(`Login`, `Admin\Login`, `Reset`(`onGet`/`onPost`),
  `Entry`(会員登録), `Entry\Activate`(有効化), `Admin\ChangePassword`,
  `Admin\CreateCustomer`, `Admin\Member`, `Admin\TwoFactorAuth`, `Admin\TwoFactorAuthSet`)。
  網羅性は `tests/Resource/CredentialParameterSensitiveParameterTest.php` が
  **discovery ベース**で固定: `BEAR\AppMeta` が見つける全 Resource クラスを reflection し、
  パターンに一致する引数に属性が無ければ fail する。許可リストではないので、新しい
  credential 引数は annotate するまでこのテストが落ちる(手動監査で `Entry::onPost` と
  `Reset::onGet` を見落とした経験からの設計)。
- **`Be\Input`/`Be\Final`/`Be\Being` 層**: 同じ名前パターンにマッチする **`string` 型の**
  コンストラクタ引数すべてに付与済み(`#[Inject]` で注入されるサービス — `$passwordHasher`
  等 — は名前は一致するが secret を持たないので `string` 型で絞る)。網羅性は
  `be/tests/Domain/CredentialConstructorParameterSensitiveParameterTest.php` が同じく
  discovery ベースで固定(`be/src/{Input,Final,Being}` 配下を走査)。`passwordHash` のような
  ハッシュ値も名前パターンに一致するので付与対象 — 平文ではないが、パターンを名前ベースに
  保つ方が例外を増やすより安全。


## `replayable` の契約

`ParamsFilterInterface` が **credential**(transport の `csrf` ではなく)を理由に値を
withhold した request は `replayable:false` としてマークされる。ログの `resource_request`
context にその旨が記録され、`SemanticLogExtractor` は **その request も抽出する** —
`Event::$replayable === false` を伴って(`Event::$id` の導出からは除外される:
「credential が withhold されたか」は操作の性質ではなく記録のされ方の性質だから)。

つまり:

| request | ログ | 抽出 |
|---|---|---|
| 成功、credential なし(例: checkout に `csrfToken` のみ) | `replayable:true`、`csrfToken` は `[FILTERED]` | される、`Event::$replayable === true` |
| 成功、credential あり(例: admin login の `password`) | `replayable:false`、`password` は `[FILTERED]` | **される**、`Event::$replayable === false`、params に `[FILTERED]` を含む |
| 失敗(code ≥ 400)、credential の有無を問わず | 記録される | されない(失敗 request は抽出しないルール — 変更なし) |

`tests/Module/EventSourcingExtractionTest.php` の
`testCredentialBearingSuccessIsExtractedAsNonReplayable` が2行目を、
`testSuccessfulRootPostExtractsExactlyOneEvent` が1行目を、`testFailedRootPostExtractsNoEvents` /
`testResetKeyIsRedactedByObserveModulesExtraCredentialSubstring` /
`testAuthKeyIsRedactedByObserveModulesExtraCredentialSubstring` が3行目を固定する。

抽出イベントストリームを再生に使う側は `Event::$replayable` を見ること。`false` の event は
params が操作を再現するのに不十分(placeholder が入っている)なので、再生エンジンは
それを再実行できない。監査証跡としては「ログイン成功」「会員登録成功」等の事実が
`replayable:false` 付きでストリームに残る。

## 未対応のギャップ一覧(まとめ)

1. ライブラリのタグ付きリリース待ち: `composer.json` は `1.x-dev` を指している。
   `bear/event-sourcing` に `0.2.0` 相当のタグが切られたら `^0.2` に張り替え、
   `composer.json` の VCS repository 行を外す。

