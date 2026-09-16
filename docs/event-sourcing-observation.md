---
layout: default
title: "event-sourcing 観測ログ — credential redaction と運用上の制約"
---

# event-sourcing 観測ログ — credential redaction と運用上の制約

`bear/event-sourcing`(`ObserveModule`)経由で記録される観測ログ・抽出イベントストリームが、
BeMart の credential(password / secretKey / resetKey / authKey / deviceToken 等)を
どう扱うかの現在の契約をまとめる。構築の経緯・意思決定ログは
[`HANDOVER.md`](HANDOVER.md#2026-09-14--event-sourcing-観測ログ-credential-redaction-と-replayablefalse-の帰結)
を参照。ここは「今どうなっているか」の正。

## redaction の仕組み(2系統、別々の層で効く)

- **request params**: `AppParamsFilter`(`src/Module/AppParamsFilter.php`)が
  `SemanticLogInvoker` に渡る前の params を redact する。ライブラリ標準の
  `SensitiveParamsFilter`(`password`/`token`/`secret`/`csrf` を含む key)に加え、
  BeMart 固有の `resetKey`/`authKey`(`Key` サフィックスでライブラリ標準にマッチしない)
  を追加で redact する。
- **response body**: `ExcludedResponseBodyStore`(`src/Module/ExcludedResponseBodyStore.php`)
  が `page://` スキームのレスポンス body を **一切保存しない**。フォームのプレフィル用に
  `resetKey`/`authKey`/`csrfToken` を body に含める `page://` レイヤー全体が対象
  (列挙型のブロックリストではなくスキーム境界で切る設計)。
  **`app://` の入れ子リソースの body は従来通り保存される** — response body 全体が
  redact 対象というわけではない。

この2系統は独立: request params の redaction は `page://`/`app://` を問わず効くが、
response body の除外は `page://` にしか効かない。

## `#[SensitiveParameter]` の付与範囲(PHP 例外スタックトレース対策)

`AppParamsFilter`/`ExcludedResponseBodyStore` は観測ログを守るが、PHP 自身が例外の
スタックトレースに引数値を埋め込む経路は別問題。これは `#[SensitiveParameter]` で守る。

`#[SensitiveParameter]` が守るのは、その引数が宣言されているフレーム **1つ**の
trace 表示のみ(呼び出しチェーンの他フレームや、同じ値を別途保持するオブジェクト/配列
までは守らない)。効く経路は「credential を受け取った `onPost`/`onPut` 自身のフレームが
スタック上にある間に、その内側で例外が発生・伝播する」場合
(例: `Login::onPost` は browser-form でない経路で `SemanticVariableException` を
re-throw する。`$this->becoming(...)` 呼び出し中に Being/Final から例外が投げられる
場合も同様)。**`#[CsrfProtected]` インターセプタは検証失敗時に例外を投げず 403 body を
セットして return するだけなので、この経路の対象ではない**(`CsrfProtectedInterceptor::invoke()`
で確認済み)。

- **Resource 層**: credential を扱う `onPost`/`onPut` 10 箇所すべてに付与済み
  (`Login`, `Admin\Login`, `Reset`, `Entry`(会員登録), `Entry\Activate`(有効化),
  `Admin\ChangePassword`, `Admin\CreateCustomer`, `Admin\Member`, `Admin\TwoFactorAuth`,
  `Admin\TwoFactorAuthSet`)。網羅性は
  `tests/Resource/CredentialParameterSensitiveParameterTest.php` が境界契約として固定。
- **`Be\Input`/`Be\Final` 層**: password 系・`secretKey`・`resetKey`・`authKey` は付与済み。
  **未対応(既知のギャップ)**: `deviceToken`
  (`SetTwoFactorAuthInput`/`TwoFactorAuthConfigured`/`TwoFactorAuthVerified`/
  `VerifyTwoFactorAuthInput`)、および `RegisterCustomerInput`/`AdminCreateCustomerInput`/
  `CreateMemberInput` の `password`。Input/Final 層の例外スタックトレースには、
  現状これらが平文で乗りうる。


## `replayable:false` の運用上の帰結

`ParamsFilterInterface` が credential を理由に redact した request は
`replayable:false` としてマークされる。ログ自体には監査用に残るが、
`SemanticLogExtractor` は抽出イベントストリームから **丸ごと除外する**。

これは失敗リクエストに限らない。
`tests/Module/EventSourcingExtractionTest.php::testCredentialBearingSuccessStaysInTheLogButIsExcludedFromEvents`
が実証する通り、**成功した(200)** admin ログインでも `password` が redact されるため
`replayable:false` になり、イベントストリームに一切現れない。

**一般則**: credential-shaped パラメータを持つ state-changing request は、成功可否に
関わらず抽出イベントストリームから消える。`#[SensitiveParameter]` を付与した
Resource 層 10 箇所が対象例(以下は代表例であり網羅ではない):

- ログイン(`Login::onPost`, `Admin\Login::onPost` — `password`)
- 会員登録(`Entry::onPost` — `password`/`password_confirm`)
- 会員本登録の有効化(`Entry\Activate::onPost` — `secretKey`)
- 管理者作成(`Admin\CreateCustomer::onPost`, `Admin\Member::onPost` — `password`)
- パスワード変更・リセット(`Admin\ChangePassword::onPost`, `Reset::onPost` —
  `currentPassword`/`changePasswordFirst`/`changePasswordSecond`/`resetKey`/`password`)
- 2FA 検証・設定(`Admin\TwoFactorAuth::onPost`, `Admin\TwoFactorAuthSet::onPut` —
  `deviceToken`/`authKey`)

抽出イベントストリームを監査証跡や event-sourcing の再生に使う場合、ログインを含む
上記フローの「成功した」という事実そのものがストリームに現れない。対策
(例: `replayable:false` でも `EventOccurred` 相当の空詳細イベントだけは残す、等)は
未着手・別途判断が必要。

## 未対応のギャップ一覧(まとめ)

1. `Be\Input`/`Be\Final` 層の `deviceToken` と 3 クラスの `password` に
   `#[SensitiveParameter]` 未付与(上記)。
2. `replayable:false` による event 消失の設計的な対策(空詳細イベントの保持等)は未着手。
3. ライブラリ側 [bearsunday/BEAR.EventSourcing#22](https://github.com/bearsunday/BEAR.EventSourcing/pull/22)
   がマージされたら、`composer.json` の `dev-redact-params` を実バージョンへ張り替える。
