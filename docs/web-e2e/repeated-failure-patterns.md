# Web+DB 検証で繰り返さない失敗パターン

この文書は、Web+DB / Hypermedia / HTTP / Browser 検証で実際に起きた失敗と、
それがなぜ起きたかを残す再発防止メモです。完成判定の正本は
[`completion-evidence-rules.md`](completion-evidence-rules.md)、日付付きの事実は
[`20260610-web-db-followups.md`](20260610-web-db-followups.md) を参照します。

## 原因の要約

今回の問題は、個別ページのバグだけではありませんでした。

- Resource / Hypermedia が green でも、HTTP form action、CSRF、Cookie、PRG、DOM 表示が未検証ならブラウザーで壊れる。
- Browser runner が `Location` や到達画面だけで pass 判定すると、業務状態が本当に作られたかを見逃す。
- Web/HTTP で業務状態を作れない場所を、直接SQL、fixture、runner専用POSTで補う誘惑がある。
- 手元ブラウザーと runner が別マシンの `localhost` を見ていると、同じURLでも違うアプリを検証してしまう。
- PHP server 起動済みの compiled DI/Twig cache が古いと、修正済みコードを見ているつもりで古い画面を検証する。

結論として、HTTP対応ハイパーメディアテストはブラウザーE2Eを置き換えるものではなく、
ブラウザーE2Eの前に業務導線を安く固定するゲートです。Browserで見つけた穴は、
先に Hypermedia または HTTP workflow に戻してから直します。

## 繰り返さないこと

| 繰り返さないこと | 実際に起きたこと | なぜ起きたか | 次回の止め方 |
|---|---|---|---|
| `Location` だけで購入や作成を pass にする | 非会員購入が `Location` evidence だけで pass 扱いになり、ブラウザーでは確認画面へ進めなかった | HTTP境界の `303 See Other`、確認画面readback、注文者情報の永続化を見ていなかった | unsafe操作は `operationEvidence` に status/action/location/readback/screenshot を残す |
| Resource green だけでフォーム完成とみなす | Resource は通るが、HTML form の `name`、`action`、`_method`、CSRF が実画面と違った | Resource引数と EC-CUBE互換フォーム名の変換境界をHTTPで固定していなかった | SQL HTML context の HTTP test で実フォーム action と送信fieldを使う |
| Webで作れない業務状態を直接seedで補う | fresh DB のゲスト購入で支払方法がなく FK 503 になった | workflow が事前に admin 支払方法作成を行い、fresh DB 直後の実導線を隠していた | setup-db の schema/master と Web操作で作る業務データを分ける。購入flowは admin setup を使わない |
| 画面到達だけで unsafe operation を pass にする | Admin CRUD の多くが一覧や詳細表示だけで、作成/更新/削除の副作用を証明していなかった | runner が URL 到達を主に見て、form/link 由来のunsafe操作とreadbackを要求していなかった | `pass` は DB-backed readback まで。できなければ fail として残す |
| runner専用の直POSTで穴を塞ぐ | 画面に action/link がない operation を、runner側でpayloadを作れば通せそうな箇所があった | 「全件green」を急ぐと、アプリが公開していない affordance をテストが発明してしまう | URLはHTML form/link/Location/ALPS relから取る。推測が必要なら止める |
| ローカルブラウザーとrunner証跡を混ぜる | 手元Chrome/in-app browserの `localhost:8080` と Codex runner の `127.0.0.1:18080` が別マシン/別サーバーだった | `localhost` は観測者のプロセスから見た境界で、同じ文字列でも同じサーバーとは限らない | 証跡には baseUrl、DB名、runner/local区分、listener確認を残す |
| 既存PHP serverやcompiled cacheを信じる | 修正後も古いTwig/DIが使われ、存在するはずのリンクがブラウザーrunnerに出なかった | 起動済み server が古い compiled context を保持していた | runner前に対象port停止、`var/tmp/html-eccube-sql-hal-app/{di,injector,twig}` 削除、server再起動 |
| 0件状態を検証しない | 受注作成リンクが `{% if count %}` の内側にあり、受注0件では新規作成できなかった | 業務状態が既にある画面だけを見て、初期状態から状態を作る導線を見ていなかった | CRUD create は fresh DB / 0件一覧から開始する regression を持つ |
| フォームNGをHTTP statusだけで見る | 空POSTや確認不一致で、例外画面・空再描画・JSONだけが返る状態を見逃した | inline error、入力値再表示、パスワード非再表示、可視エラーUI数を見ていなかった | NG case は同じフォーム画面、inline error、再表示/非再表示、screenshot を確認する |
| file upload を通常POST扱いにする | CSV/template upload で `$_FILES` がResourceへ渡らず、空CSVや `message=csv` になった | BEAR の `#[InputFile]` / multipart境界を Resource/HTTP/browser で統一していなかった | Resourceは `FileUpload::fromFile()`、HTTP/browserは同じHTML formに multipart submit |
| JS依存のhidden field生成を見ない | CSV設定で実画面は `csvOutput[]` / `csvNotOutput[]` から hidden `columns[...]` を作るが、HTTP test は直接 `columns` を送っていた | ブラウザーDOMとsubmit時の実field shapeを確認していなかった | HTTP test は実formのfield名を正とし、JS生成fieldがある場合はBrowserで確認する |
| fake/noop境界を本物の完成と混同する | メール、2FA、template select、maintenance/security設定などで、fake/noop/runtime境界を越えて完成扱いしそうになった | 外部境界や本番ファイル操作の安全なreadback条件を明確にしていなかった | fake/noop/runtime境界は明記する。本番SMTP/決済/破壊的ファイル操作は targetOut |
| テスト都合でResourceやDTOを作る | 手札がないのに `PUT /admin/customer` や 083 商品規格更新を作れば通せる箇所があった | OpenAPI/ALPS/HTML/Be Input が揃う前に、テストgreenを優先しそうになった | Resource/OpenAPI/ALPS/HTML form/readback が揃わない場合は fail/follow-up として止める。083 は ProductClass matrix の read/update contract が揃うまで green にしない |

## 次回の作業順

1. まず Hypermedia workflow で業務ストーリーを表現する。
2. 同じ workflow を HTTP projection で実 cookie / CSRF / form action / PRG へ投影する。
3. Browser runner で DOM visibility、クリック、multipart、JS生成field、スクリーンショットを確認する。
4. Browserで穴を見つけたら、先に Hypermedia または HTTP regression に戻す。
5. 戻せない場合は実装を作らず、`fail` または `targetOut` と理由を残す。
6. 修正後は `hypermedia -> http -> browser` の順に再実行する。

## 証跡に必ず残すもの

- run ID、base URL、DB名、APP context、runner/local browser の区別。
- unsafe操作の method/path/action/status/location。
- readback した画面URLと主要テキスト。
- screenshot または targetOut 理由。
- Web/HTTPで業務状態を作れなかった場合の失敗地点。
- 自信がない境界、採用しなかった回避策、次に必要な設計判断。

## 判断基準

迷った場合は green にしません。
テストがアプリに存在しないURL、payload、状態を発明しないことを優先します。
