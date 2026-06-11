# Web+DB 完成判定の証跡ルール

この文書は、Web+DB 検証で同じ失敗を繰り返さないための恒久ルールです。
日付付きの結果や follow-up は `20260610-web-db-followups.md` に残しますが、
判定そのものはここを正とします。
なぜこのルールが必要になったかは
[`repeated-failure-patterns.md`](repeated-failure-patterns.md) に残します。

## 完成の単位

`pass` は画面が表示されたことではなく、公開された affordance を使って
業務状態を作り、その状態を別の画面または同じ画面の readback で確認できたことを表します。

unsafe operation を含む feature は、次の全てを満たすまで `pass` にしません。

1. 直前表現の HTML form action、link href、`Location`、または ALPS rel から URL を取る。
2. 同じ browser context の cookie と CSRF token で HTTP 境界を送信する。
3. HTML context では `303 See Other` の PRG、または期待された HTTP error を確認する。
4. 成功系は DB-backed readback を画面で確認する。削除は一覧非表示または 404 を確認する。
5. 結果 JSON の `operationEvidence` に method/path/status/action/location/readback/screenshot を残す。

`GET` だけの画面到達、`Location` ヘッダだけ、Resource 単体 green だけでは
業務操作の `pass` にはしません。

## NG フォームの単位

フォーム NG は HTTP status だけでは不十分です。代表ケースごとに、
同じフォーム画面へ戻ること、inline error が見えること、入力値が必要な範囲で再表示されること、
パスワードが再表示されないことを確認します。

JSON/HTTP error 境界の場合は、期待 status と日本語メッセージを記録します。
HTML フォームで例外画面や空の再描画になる場合は fail です。

## Browser 不具合を見つけた時の順序

1. まず対応する Hypermedia または HTTP workflow に戻して、同じ欠陥を赤で表現する。
2. 既存 workflow の形で表現できない場合は、手札不足として止める。
3. 実装修正は、赤い regression ができてから行う。
4. `hypermedia -> http -> browser` の順で再実行する。
5. browser evidence だけで完了扱いにしない。

## HTTP ハイパーメディアの役割

HTTP 対応ハイパーメディアテストは、ブラウザー E2E の代替ではなく、
ブラウザー E2E の時間と手間を減らす前段ゲートです。

- Resource workflow は、業務ストーリーと rel/Location の意味を固定する。
- HTTP workflow は、Cookie、CSRF、HTML form action、method override、PRG、download/multipart の実境界を固定する。
- Browser runner は、HTTP workflow で表現済みの導線が実DOMと画面で破綻していないことを、代表証跡として確認する。
- Browser で新しい不具合を見つけたら、先に Hypermedia または HTTP workflow へ戻す。戻せない場合は、runner 専用処理を足さず fail/follow-up として止める。
- ただし CSS/JS/DOM visibility、実フォームの `name`、ファイル選択、クリックでだけ現れる affordance は Browser でしか見えないため、HTTP green だけで完成扱いにしない。

## 環境境界

runner の `baseUrl` は runner プロセスから見たネットワーク境界です。
手元 Chrome / in-app browser が別マシンで動く場合、同じ `localhost` / `127.0.0.1`
でも別アプリを見ていることがあります。

Web+DB run には必ず次を残します。

- `baseUrl`
- `DATABASE_URL` の redacted 値と DB 名
- `APP_CONTEXT`
- `public/page.php` を見ていること
- コード変更後の HTTP/browser run は、PHP server 起動前に `var/tmp/html-eccube-sql-hal-app/{di,injector,twig}` を消して compiled DI/Twig の stale evidence を避ける。既に起動済みの server がある場合は再起動する。
- runner 証跡か local browser 証跡かの区別

local browser で確認する場合は、そのマシンで同じ branch / DB / PHP server を起動し、
runner 結果 JSON とは別の local browser evidence として扱います。

## 止める条件

次の状態では、実装を作り足して `pass` にしません。

- form action/link/Location/rel がなく、操作 URL を推測する必要がある。
- readback できる Resource / SQL read model がない。
- Web/HTTP で業務状態を作れず、直接 SQL seed や fixture boundary で補う必要がある。
- 実 SMTP、外部決済、本番運用ファイルなど、壊すと戻せない外部境界に触る必要がある。
- テストのためだけの wrapper、route mapper、fake store、固定 dummy row が必要になる。

この場合は `fail` または理由付き `targetOut` として残し、follow-up に自信のない点を記録します。

## 機械的な guard

`tests/Hypermedia/WorkflowBackdoorStateCoverageTest.php` は、少なくとも次を検査します。

- workflow が fixture boundary で業務状態を作っていないこと。
- unsafe transition が direct `page://self/...` URI で作られていないこと。
- purchase flow が admin order/payment 作成で初期状態を補っていないこと。
- Web+DB runner が `Location` だけで非会員購入を pass に戻していないこと。
- Web+DB runner が unsafe operation を screenshot 付き setup evidence なしに pass にしていないこと。
- runner 証跡が local browser と同一視できない network boundary を明記していること。

guard で検出できないものは、この文書と follow-up 台帳に残し、
次に同じ形で失敗した時点で guard へ昇格します。
