# リンクのストーリー

1 本の URI（`#link=type:rel?query` 付き）で、クライアントがリンクを辿る流れを書く。行は `LinkStories.php` に 1 度だけ書き、次のテストが同じ行を流す。

| テスト | 流し方 | 行 |
|---|---|---|
| `LinkStoryTest` | in-process、Fake。`followable()`・`forAdminOrders()`・`forMembers()`・`forBuyers()` の行は、セマンティックログのツリーを `golden/<ストーリー名>.stree.txt` と照合する | `followable()`・`forAdminOrders()`・`forMembers()`・`forBuyers()`・`unfollowable()`・`uploads()` |
| `HttpLinkStoryTest` | HTTP、Fake の HAL（`tests/Http/admin-json-index.php`、管理者がログイン済み）。リンクは HAL の `_links` から読む | `followable()`・`unfollowable()`・`uploads()`（`forAdminOrders()`・`forMembers()`・`forBuyers()` は流さない） |
| `HtmlLinkStoryTest` | HTTP、Fake の HTML（`tests/Http/index.php`）。リンクはページの `<a>`・`<form>` から読む | `forGuests()` だけ（管理者のフォームは PUT・DELETE を `_method` 付きの POST で送り、クライアントはそれを辿らない） |
| `PersistedLinkStoryTest` | in-process、SQL（tests/Hypermedia と同じ MySQL が要る） | `persisted()` |

golden では、リクエストの順、method、params、becoming、応答の code が、そのまま読める。ツリーの根は `link_request` で、最初の URI と辿るリンク（`type:rel`。リンクの query は、それを辿ったリクエストの params に出る）を示す。辿ったリクエストはその下に並び、閉じの `link_response` は返った code を示す。

ツリーの後の `events:` は、bear/event-sourcing の抽出器がこのログからイベントとして取り出すリクエスト（再生で再び実行されるもの）。連鎖の途中で成功した POST・PUT・DELETE が 1 件ずつ並び、GET だけの連鎖と、状態を変えるリクエストが成功する前に止まった連鎖は `(none)` になる。

遷移を「辿った」と数えるのは、golden に記録されたステップだけ（`composer story:flows`・`composer story:uncovered`）。400 などで連鎖が止まった行は、止まった所までを数え、その先の rel は辿っていない。行が宣言する `flow-*` と辿る遷移の照合も同じ rel を使うので、止まった先の rel だけが持つフローは宣言できない。201・303 の Location の GET はリンクを消費しない。`persisted()`・`uploads()`・`unfollowable()` の行と、まだ golden を記録していない行は、URI の rel を数える。`UPDATE_GOLDEN=1` で記録する間は、宣言の照合を飛ばす（記録のあとの `composer cs` が照合する）。

## URI の書き方

URI は `uri_template()`（RFC 6570）で組み、値は行ごとに渡す。文字列の値は `{?form*}` のように書く。フォームの値は `LinkStories::form()` で CSRF トークンを足す。

```php
uri_template('page://self/admin/layout/layout-list#link=self:goLayout{?layoutId},self:doUpdateLayout{?form*}', [
    'layoutId' => 'lo-pc-default',
    'form' => self::form(['layoutId' => 'lo-pc-default', 'name' => 'Story Layout']),
]),
```

list や入れ子の値（`productCodes[]` など）を含むリンクは、そのリンクのクエリーだけを `http_build_query()` で書く。`{?form*}` に list を入れると `productCodes=a&productCodes=b` になり、PHP は最後の 1 つしか読まない。`http_build_query()` の形は `LinkType::listFromUri()` がそのまま読む。

```php
'page://self/admin/product-list#link=self:doBulkUpdateProductStatus?' . http_build_query(
    self::form(['productStatus' => '2']) + ['productCodes' => ['admin-active-001', 'admin-withdrawn-001']],
),
```

リソースクライアントは既定ですべてのリンクを GET で辿る。in-process のストーリーは、`LinkerInterface` に BEAR.DevTools の `DeclaredMethodLinker` を束縛している（`StoryModule`、SQL では `WorkflowDbSession`）。このリンカーは、リンクを `#[Link]` に宣言した method で辿り、303・201 の Location を GET で辿る。

## 行の足し方

1. `LinkStories::forGuests()`（ゲストが辿る）か `forAdmins()`（管理者が辿る）に行を足す。`followable()` はこの 2 つを合わせたもの。会員として辿る行は `forMembers()`・`forBuyers()` に、注文を読み書きする管理者の行は `forAdminOrders()` に足す。キーは、そのストーリーが示すことを書いた文にする。値は URI、終点の code、終点の path、終点の body の部分一致（キーの `.` で入れ子の配列を指す）、**そのストーリーが辿る `flow-*` タグ**。タグは `alps.json` の遷移に付いたもので、2 つのフローを辿るなら配列で書く。
2. `UPDATE_GOLDEN=1 vendor/bin/phpunit tests/Story` でツリーを記録する。
3. 新しい golden を読み、辿った順と各ステップの code が意図どおりかを確かめてから commit する。`followable()`・`forAdminOrders()`・`forMembers()`・`forBuyers()` の行は、golden がないと失敗する。
4. `composer story:flows` で `docs/story-flows.md` と、行の上の図のリンクを作り直す。行は `'<ストーリー名>' => [` の形で書く（ループで組み立てた行にはリンクを付けられず、`composer cs:story` が失敗する）。

`forAdminOrders()` は、注文を読み書きする管理者の行。注文は in-process の Fake にだけあり、HTTP の 2 つのアプリは持たないので、in-process だけで流す。

会員のストーリーは in-process だけで流す（HTTP の 2 つのアプリには会員のログイン状態がない）。`forMembers()` は `StoryModule::MEMBER_ID`（プロフィール・住所・カート・購入前の注文がある会員）、`forBuyers()` は `StoryModule::BUYER_ID`（注文履歴だけがある会員。プロフィールがないので `/mypage` には入れず、履歴のページから始める）としてログインして辿る。

辿れないことを示すストーリーは `unfollowable()`（URI、例外、`flow-*`）に、最初のリクエストでファイルを送るストーリーは `uploads()`（URI、query、ファイル、code、path、`flow-*`）に書く。作ったものを読み戻すストーリー（201 の Location など）は Fake では保存されないので `persisted()` に書く（DB が付ける ID が毎回変わるので、ツリーは照合しない）。

## ALPS との突き合わせ

行の最後の要素は、そのストーリーが辿るフローの `flow-*` タグ（`alps.json` の遷移の `tag` にある）。どのフローの、どの遷移をそのストーリーが見せるのかを、読む人に伝える。`alps.json` はテストを知らず、ストーリーが `flow-*` で ALPS を指す。

テストは次を確かめる。`composer cs:story` はすべての行を、`testFollows*`・`testRefuses`・`testUploads` は流す行の上の 3 つを見る。

- 宣言したタグが `alps.json` にある
- 行が辿る rel（golden のある行は golden が記録したもの、ない行は URI の `#link=`）のうち `flow-*` タグを持つものはすべて、宣言したタグのどれかを持つ（複数宣言したときは、宣言したタグのどれもが、どれかの rel に付いている）
- `flow-*` タグを持たない遷移（`goHelp*` など）はどのフローにも属さないので、照合では中立に扱う
- 辿る遷移に `flow-*` タグがあれば、行はフローを宣言する。フローを宣言しない（図のリンクも付かない）のは、辿る遷移のどれにも `flow-*` タグがない行だけ

食い違えば、行か `alps.json` のタグの正しい方を直す。

```bash
composer story:uncovered   # flow タグごとに、そのフローを宣言したストーリーが辿らない遷移を列挙し、食い違えば失敗する
composer story:flows       # docs/story-flows.md と、行の上の図のリンクを作り直す
```

[`docs/story-flows.md`](../../docs/story-flows.md) は、`flow-*` ごとの ALPS の図の URL、そのフローを宣言した行、そのフローを宣言した行が辿らない遷移の表（別のフローを宣言した行が辿る遷移も載る）。`flow-*` を宣言した行には、その行のフローで絞った公開の図へのリンク（`// @see https://be-framework.github.io/BeMart/alps.json.html?tag=…`）が真上に付く。`composer cs`（`cs:story`）と `StoryCoverageTest` は、この表かリンクが古いか、リンクが無いと失敗する。

## 書けないとき

| 起きたこと | 意味 | 対応 |
|---|---|---|
| `LinkRelException` | 今いるリソースのメソッドに、その rel の `#[Link]` がない | リソースの宣言の問題。行で回避せず、止めて報告する |
| `ParameterException` | 必要な値（実行中にできる ID など）が URI に書けない | 同上 |
| `LinkException` | 辿った先の 201・303 の Location が、さらに転送する（リンクが辿る Location は 1 つ） | 実装の欠けなら報告する。決まった扱いなら `unfollowable()` に行を足し、キーに理由を書く |
| golden が毎回変わる | 実行ごとに変わる値（時刻、採番）がツリーに出ている | 値を作る側を注入にして、`StoryModule` で固定の実装を束縛する（`ClockInterface`、`OrderNoIssuerInterface`、`InquiryTicketIssuerInterface` と同じ形）。正規表現で消さない |

`src/` の `#[Link]` の追加と、`LinkStoryTest`・`StoryModule` の変更は設計の判断になる。行と golden を足す作業の中ではしない。
