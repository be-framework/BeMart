# リンクのストーリー

1 本の URI（`#link=type:rel?query` 付き）で、クライアントがリンクを辿る流れを書く。行は `LinkStories.php` に 1 度だけ書き、次のテストが同じ行を流す。

| テスト | 流し方 | 行 |
|---|---|---|
| `LinkStoryTest` | in-process、Fake。`followable()` の行は、セマンティックログのツリーを `golden/<ストーリー名>.stree.txt` と照合する | `followable()`・`unfollowable()`・`uploads()` |
| `HttpLinkStoryTest` | HTTP、Fake の HAL（`tests/Http/admin-json-index.php`、管理者がログイン済み）。リンクは HAL の `_links` から読む | 同上 |
| `HtmlLinkStoryTest` | HTTP、Fake の HTML（`tests/Http/index.php`）。リンクはページの `<a>`・`<form>` から読む | `forGuests()` だけ（管理者のフォームは PUT・DELETE を `_method` 付きの POST で送り、クライアントはそれを辿らない） |
| `PersistedLinkStoryTest` | in-process、SQL（tests/Hypermedia と同じ MySQL が要る） | `persisted()` |

golden では、リクエストの順、method、params、becoming、応答の code が、そのまま読める。ツリーの根は `link_request` で、最初の URI と辿るリンク（`type:rel`。リンクの query は、それを辿ったリクエストの params に出る）を示す。辿ったリクエストはその下に並び、閉じの `link_response` は返った code を示す。

ツリーの後の `events:` は、bear/event-sourcing の抽出器がこのログからイベントとして取り出すリクエスト（再生で再び実行されるもの）。連鎖の途中で成功した POST・PUT・DELETE が 1 件ずつ並び、GET だけの連鎖と、状態を変えるリクエストが成功する前に止まった連鎖は `(none)` になる。

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

1. `LinkStories::forGuests()`（ゲストが辿る）か `forAdmins()`（管理者が辿る）に行を足す。`followable()` はこの 2 つを合わせたもの。キーは、そのストーリーが示すことを書いた文にする。値は URI、終点の code、終点の path、終点の body の部分一致（キーの `.` で入れ子の配列を指す）。
2. `UPDATE_GOLDEN=1 vendor/bin/phpunit tests/Story` でツリーを記録する。
3. 新しい golden を読み、辿った順と各ステップの code が意図どおりかを確かめてから commit する。`followable()` の行は、golden がないと失敗する。

辿れないことを示すストーリーは `unfollowable()`（URI と例外）に、最初のリクエストでファイルを送るストーリーは `uploads()` に書く。作ったものを読み戻すストーリー（201 の Location など）は Fake では保存されないので `persisted()` に書く（DB が付ける ID が毎回変わるので、ツリーは照合しない）。

## ALPS との突き合わせ

`followable()` の行が辿る遷移には、`alps.json` のその遷移の `link` に、行の golden を指す `rel: story` を 1 行ずつ足す。1 つの遷移を複数のストーリーが辿るなら、ストーリーの数だけ足す（`describedby` はそのまま残す）。PHP 側に足すものはなく、行の `#link=type:rel` の rel が ALPS の id になる。

```json
"link": [
  {"rel": "story", "href": "tests/Story/golden/admin-top-leads-to-the-shop-settings.stree.txt"}
]
```

```bash
composer story:uncovered   # ストーリーのない遷移を flow ごとに列挙し、ALPS の link とストーリーが食い違えば失敗する
```

食い違いは `composer cs`（`cs:story`）と `StoryCoverageTest` でも失敗する。

- ストーリーが辿るのに `story` link がない: 遷移の `link` に、そのストーリーの golden を足す
- `story` link の golden がない、またはそのストーリーがその rel を辿らない: link を直すか、ストーリーを戻す
- ストーリーが辿る rel が `alps.json` にない: rel の綴りか、ALPS の遷移を直す

`unfollowable()` の行は辿れないことを示すので、`uploads()`・`persisted()` の行は golden がないので、`link` に足さない。

## 書けないとき

| 起きたこと | 意味 | 対応 |
|---|---|---|
| `LinkRelException` | 今いるリソースのメソッドに、その rel の `#[Link]` がない | リソースの宣言の問題。行で回避せず、止めて報告する |
| `ParameterException` | 必要な値（実行中にできる ID など）が URI に書けない | 同上 |
| golden が毎回変わる | 実行ごとに変わる値がツリーに出ている | `LinkStoryTest::VOLATILE` に足す前に報告する |

`src/` の `#[Link]` の追加と、`LinkStoryTest`・`StoryModule` の変更は設計の判断になる。行と golden を足す作業の中ではしない。
