# リンクのストーリー

1 本の URI（`#link=type:rel?query` 付き）で、クライアントがリンクを辿る流れを書く。行は `LinkStories.php`、実行は `LinkStoryTest.php`（in-process、Fake）。

各ストーリーのセマンティックログのツリーを `golden/<ストーリー名>.stree.txt` と照合する。リクエストの順、method、params、becoming、応答の code が、そのまま読める。

## 行の足し方

1. `LinkStories::followable()` に行を足す。キーは、そのストーリーが示すことを書いた文にする。値は URI、終点の code、終点の path、終点の body の部分一致。
2. `UPDATE_GOLDEN=1 vendor/bin/phpunit tests/Story` でツリーを記録する。
3. 新しい golden を読み、辿った順と各ステップの code が意図どおりかを確かめてから commit する。golden がないと失敗する。

辿れないことを示すストーリーは `unfollowable()`（URI と例外）に、最初のリクエストでファイルを送るストーリーは `uploads()` に書く。作ったものを読み戻すストーリー（201 の Location など）は Fake では保存されないので `persisted()` に書き、`PersistedLinkStoryTest` が SQL の context で流す（tests/Hypermedia と同じ MySQL が要る。DB が付ける ID が毎回変わるので、ツリーは照合しない）。

## 書けないとき

| 起きたこと | 意味 | 対応 |
|---|---|---|
| `LinkRelException` | 今いるリソースのメソッドに、その rel の `#[Link]` がない | リソースの宣言の問題。行で回避せず、止めて報告する |
| `ParameterException` | 必要な値（実行中にできる ID など）が URI に書けない | 同上 |
| golden が毎回変わる | 実行ごとに変わる値がツリーに出ている | `LinkStoryTest::VOLATILE` に足す前に報告する |

`src/` の `#[Link]` の追加と、`LinkStoryTest`・`StoryModule` の変更は設計の判断になる。行と golden を足す作業の中ではしない。
