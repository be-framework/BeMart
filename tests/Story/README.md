# リンクのストーリー

1 本の URI（`#link=type:rel?query` 付き）で、クライアントがリンクを辿る流れを書く。行は `LinkStories.php` に 1 度だけ書き、次のテストが同じ行を流す。

| テスト | 流し方 | 行 |
|---|---|---|
| `LinkStoryTest` | in-process、Fake。セマンティックログのツリーを `golden/<ストーリー名>.stree.txt` と照合する | `followable()`・`unfollowable()`・`uploads()` |
| `HttpLinkStoryTest` | HTTP、Fake の HAL（`tests/Http/admin-json-index.php`、管理者がログイン済み）。リンクは HAL の `_links` から読む | 同上 |
| `HtmlLinkStoryTest` | HTTP、Fake の HTML（`tests/Http/index.php`）。リンクはページの `<a>`・`<form>` から読む | `forGuests()` だけ（管理者のフォームは PUT・DELETE を `_method` 付きの POST で送り、クライアントはそれを辿らない） |
| `PersistedLinkStoryTest` | in-process、SQL（tests/Hypermedia と同じ MySQL が要る） | `persisted()` |

golden では、リクエストの順、method、params、becoming、応答の code が、そのまま読める。ツリーの根は `link_request` で、最初の URI と辿るリンク（`type:rel`。リンクの query は、それを辿ったリクエストの params に出る）を示す。辿ったリクエストはその下に並び、閉じの `link_response` は返った code を示す。

ツリーの後の `events:` は、bear/event-sourcing の抽出器がこのログからイベントとして取り出すリクエスト（再生で再び実行されるもの）。連鎖の途中の POST・PUT・DELETE が 1 件ずつ並び、GET だけの連鎖と途中で止まった連鎖は `(none)` になる。

## 行の足し方

1. `LinkStories::forGuests()`（ゲストが辿る）か `forAdmins()`（管理者が辿る）に行を足す。`followable()` はこの 2 つを合わせたもの。キーは、そのストーリーが示すことを書いた文にする。値は URI、終点の code、終点の path、終点の body の部分一致。
2. `UPDATE_GOLDEN=1 vendor/bin/phpunit tests/Story` でツリーを記録する。
3. 新しい golden を読み、辿った順と各ステップの code が意図どおりかを確かめてから commit する。golden がないと失敗する。

辿れないことを示すストーリーは `unfollowable()`（URI と例外）に、最初のリクエストでファイルを送るストーリーは `uploads()` に書く。作ったものを読み戻すストーリー（201 の Location など）は Fake では保存されないので `persisted()` に書く（DB が付ける ID が毎回変わるので、ツリーは照合しない）。

## 書けないとき

| 起きたこと | 意味 | 対応 |
|---|---|---|
| `LinkRelException` | 今いるリソースのメソッドに、その rel の `#[Link]` がない | リソースの宣言の問題。行で回避せず、止めて報告する |
| `ParameterException` | 必要な値（実行中にできる ID など）が URI に書けない | 同上 |
| golden が毎回変わる | 実行ごとに変わる値がツリーに出ている | `LinkStoryTest::VOLATILE` に足す前に報告する |

`src/` の `#[Link]` の追加と、`LinkStoryTest`・`StoryModule` の変更は設計の判断になる。行と golden を足す作業の中ではしない。
