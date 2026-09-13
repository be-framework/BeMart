---
user-invocable: true
name: bear-observe
description: BEAR.Sunday アプリに観測用の dev 文脈を設置し、1 リクエストが何をしたか(リソースの入れ子・キャッシュの hit/miss・保存と無効化)を木で読む。宣言した意図と照合し、食い違えばアプリかライブラリかを切り分けて報告する。決着しなければ xstep に降りる。Use when user says "ログを見て", "キャッシュログ", "cache log", "何が起きたか", "observe", "リソースの動きを追って", "デバッグして", "遅い", "キャッシュが効いてない", "キャッシュが古い", "purge が効かない", "304 が返らない", "アプリかライブラリか切り分けて", or asks what a BEAR.Sunday request actually did.
---

# bear-observe — 1 リクエストが何をしたかを木で読む

BEAR.Sunday の欠陥の多くは沈黙する。**キャッシュが一度も効いていないリソースも、正しい答えを返し続ける**。
テストは緑のまま、遅いだけ、あるいは古いだけになる。見るのはコードではなくログで、順番は決まっている。

```text
1. 設置     観測用の dev 文脈をアプリに書く          (harness/setup.php)
2. 実証     配線が生きていることを束縛から確かめる    (harness/check.php)
3. 観測     リクエストを 1 本流して木を読む           (harness/tree.php)
4. 照合     宣言した意図が、どのイベント列で現れるか
5. 切り分け  食い違ったら、アプリの問題かライブラリの問題か
6. 報告     根拠つきで、どちらかを言う
```

**この手順を実行するのはエージェントで、ユーザーではない。** ユーザーがするのはスキルを入れることと、
「ログを見て」と言うことだけ。コマンドをユーザーに貼って待たない。

以下 `<skill>` は、このファイルのあるディレクトリ。`${CLAUDE_PLUGIN_ROOT}` があればそれ、
プロジェクトに `cp` したなら `.claude/skills/bear-observe`。

## 呼ばれ方 — どこから入るか

「キャッシュログを確認して」「何が起きたか見て」「遅い理由を調べて」で入る。

1. **読める木があるなら、まず読む。** `var/log/*/observe/latest.json` があれば `tree.php` を実行する。
   設置は済んでいるので §1 は飛ばす。mtime が前回見たときから動いていなければ、**そう言う** —
   古い木を新しい実行の証拠にしない
2. 木が無いときだけ §1 の判定に降りる。設置してから、ユーザーに「どのリクエストを見ますか」と聞く
   (勝手にリクエストを選ばない)。木が薄い・不審なときは §2 の `check.php` で配線から確かめる
3. 木を読み、§3 の表で問いに答える。ユーザーが問いを持っていなければ、木から言えることを言う:
   何回リソースが走ったか、hit したか、保存されたか、時間はどこに落ちたか
4. 宣言どおりに動いているかを問われたら §4、食い違ったら §5。降りるときは何を確かめに行くかを先に言う

## 0. 前提を 1 度だけ確かめる

```bash
composer show bear/event-sourcing bear/query-repository 2>&1 | head -3
```

どちらか無ければ入れる(未リリースの間は dev):

```bash
composer require --dev bear/event-sourcing:1.x-dev bear/query-repository:1.x-dev
```

## 正典ドキュメント(推測で補完しない)

BEAR.QueryRepository の `docs/` と `demo/` は `.gitattributes` で `export-ignore` — 通常の
`composer require` の vendor には**入らない**。GitHub で読むか
`composer reinstall bear/query-repository --prefer-source` で取る。

| 知りたいこと | どこ |
|---|---|
| **語彙全部と読み方(まずこれ)** | [docs/reading-the-log.md](https://github.com/bearsunday/BEAR.QueryRepository/blob/1.x/docs/reading-the-log.md) |
| ログが答える問いと保証の境界 | [docs/what-the-log-proves.md](https://github.com/bearsunday/BEAR.QueryRepository/blob/1.x/docs/what-the-log-proves.md) |
| 設計根拠・コスト実測・既定オフの理由 | [docs/why-the-log-records-everything.md](https://github.com/bearsunday/BEAR.QueryRepository/blob/1.x/docs/why-the-log-records-everything.md) |
| 各 context のスキーマ(28 種) | [docs/schemas/context/](https://github.com/bearsunday/BEAR.QueryRepository/tree/1.x/docs/schemas/context) — 各イベントの `schemaUrl` が正典 |
| 動く実例(自己検証つき) | [demo/](https://github.com/bearsunday/BEAR.QueryRepository/tree/1.x/demo) |

スキーマ検証: `vendor/bin/validate-semantic-log.php <log.json> <schema-dir>`(`--prefer-source` なら
`vendor/bear/query-repository/docs/schemas/context`。dist ならスキーマをアプリ側にコピーして
リポジトリ管理する — スキーマは公開契約なので固定して持つことに意味がある)。

## 1. 設置 — 要るかどうかを先に決める

観測はもう動いているかもしれない。動いているなら**アプリに 1 文字も書かない**。
`src/Module/DevModule.php` を見て 3 つに分ける:

| 見えたもの | すること |
|---|---|
| 無い | `setup.php` を走らせる。何を書くかを先に 1 行で言う(下表) |
| ある、`SemanticLogInvoker` を含む | 設置済み。§2 の `check.php` だけ走らせて §3 へ |
| ある、含まない | アプリ自身の `DevModule`。`setup.php` は上書きせず `DevModule.php.observe` を隣に置くので、畳み込みはこちらの仕事 |

`setup.php` は冪等で、既存ファイルは残す(`--force` は自前の `DevModule` も含めて全部を上書きする)。それでも「観測が動いているか」の判定に
**ファイルの有無を使わない** — 判定は §2 の `check.php` で、束縛から取る。

```bash
php <skill>/harness/setup.php .
php <skill>/harness/setup.php . bin/admin.php   # 別の入口の文脈を観測する
```

これが書くもの:

| ファイル | 役目 |
|---|---|
| `src/Module/DevModule.php` | 観測文脈。invoker を包み、event sourcing とキャッシュログを install し、プールを永続にする |
| `src/Module/ObserveLoggerProvider.php` | `#[CacheLog]` の logger を無印の `SemanticLoggerInterface` にも配る |
| `src/Module/DevPoolProvider.php` | リクエストをまたいで生き残るファイルプール |
| `bin/dev.php` | 観測文脈で CLI から叩く入口 |

`public/index.php` は**変えない**。flush はキャッシュログモジュールの shutdown sink が持つので、
アプリが自分で書き出す行は要らない。

**このファイル群を自分で書き起こさない。** `templates/` を読んで手で書き直すと、`rename()` した invoker の
差し替えや 2 つの束縛キーが同じ logger を指す形が微妙にずれる。ずれても例外は出ず、木が薄くなるだけだ
(§2 の表がその一覧)。namespace は `composer.json` から、文脈名は渡した入口(既定 `public/index.php`)から取る。
判断が要るのは、アプリが自前の `DevModule` を持っていたときの畳み込みだけで、そこは `setup.php` が
`.observe` を隣に置いて手に渡す。

畳み込むときの罠が 1 つある。**包んだモジュールは「足す」のではなく「置き換える」**。アプリが既に
`QueryRepositoryModule` を持つ(BEAR.Package 経由など)のに、上から `module: new QueryRepositoryModule()`
を足すと **Ray.Aop の pointcut が累積してインターセプタが 2 回走る** — 1 リクエストで参照 2 回・書き込み
2 回、木には同じ URI が自分の中に入れ子で現れる。既存グラフに記録だけ足すなら、包まずに
`bind(SemanticLoggerInterface::class)->annotatedWith(CacheLog::class)` の 1 本だけを差し替える。

**入口が複数あるアプリでは、観測したい入口を第 2 引数で渡す。** 文脈はその入口が渡す文脈リテラルから
`cli-` `prod-` `dev-` を落として `cli-dev-` を付けたもの(`bin/app.php` の `'cli-hal-api-app'` なら
`cli-dev-hal-api-app`)。アプリ自身の語(`stage-` など)は文脈の一部なので残る。`bin/dev.php` は 1 つしか
無いので、入口を切り替えるときは `bin/dev.php` の文脈リテラルを手で書き換える — 古いものが残っていれば
`note` 行がそう言う。**ここで `--force` を使わない**: `src/Module/*.php` も上書きするので、アプリ自身の
`DevModule` が消える。ルートに `autoload.php` が無く `vendor/autoload.php` だけの構成でも動く。

`setup.php` は文脈名(`cli-dev-hal-app` 等)とログのパスを出す。以降その文脈名を使う。

## 2. 実証 — 「入れた」で終わらせない

観測の失敗は全部無音だ。アプリは正しく答え、ログが薄くなるだけになる。束縛から確かめる:

```bash
php <skill>/harness/check.php . <context>
```

5 行すべて PASS でなければ観測は始まっていない。各 FAIL は「それが隠す欠落」を自分で書く:

| 点検 | 落ちると見えなくなるもの |
|---|---|
| one logger for both keys | `resource_request` は残り、`get` / `save_value` が消える(2 つ目の logger を誰も flush しない) |
| invoker is decorated | `resource_request` が 1 つも無く、キャッシュのスコープが宙に浮く |
| resource object / etag pool survives | 毎回 miss になり、hit も 304 も原理的に出ない |
| a sink owns the flush | 木は組み上がって shutdown で捨てられる。ファイルが 1 つも増えない |

FAIL が残るなら、アプリの `AppModule` が同じ束縛を後から上書きしていないかを見る
(`install()` は既存を上書きしないが、`bind()` は上書きする)。到達しない理由が束縛に無ければ
`error_log`(FPM なら SAPI のエラーログ)を見る。sink の拒否・二重 arm・書けない先は全部そこに出る。

### PASS でも木が薄いとき

`check.php` が見るのは束縛だけで、**インターセプタが織られたかは見ていない**。ここを外すと、
測っているものが別物になる:

- **`final class` はインターセプタを受け取れない**。Ray.Aop はクラスを継承して織るので、`final` なリソースの
  `#[Cacheable]` は静かに無効になり、**そのリソースの `get` スコープが miss すら出さずに消える**。
  木全体が空になるとは限らない — final でない他のリソースは普通に出るので、欠けているのは
  1 本の枝だけのことがある。検査:

```php
get_class($injector->getInstance($class)) !== $class;  // false なら織られていない
$injector->getInstance($class)->bindings;               // メソッド名 => 付いたインターセプタ
```

- **qualifier は `BEAR\RepositoryModule\Annotation\*`**(`CacheLog`, `ResourceObjectPool`, `TagsPool`, `EtagPool`)。
  `BEAR\QueryRepository\Annotation\` にも同名クラスがあり、**間違えても例外は出ない** — 自分の
  `getInstance()` が同じ間違った key で答えるので「束縛は効いている」ように見える。プールの中身を数えて確かめる
- **weave が変わったら `composer clean`**(コンパイル済み DI が残っていると直した後も織られない)
- **時間差で判定しない**。プロセス内では refill も 1ms 台で hit と区別できない。判定は必ずイベントで行う
- **プリセットの実数**は `Expiry`: `short` 60 / `medium` 3600 / `long` 86400 / `never` 31536000 秒。
  アプリが `StorageExpiryModule` で上書きするので、数値を書き写さず injector から `Expiry` を取って
  `getTime()` で解決する

## 3. 観測 — リクエストを流して木を読む

```bash
php bin/dev.php get '/orders?order_id=O-1'
php <skill>/harness/tree.php
```

`tree.php` は引数なしなら `var/log/*/observe/latest.json` の最新を読む。パスを渡せばそれを読む。
`ls -t var/log/<context>/observe/*.json` で過去のセッションが新しい順に並ぶ。

**読むのは今回の実行が書いたものか。** `latest.json` はリクエストごとに入れ替わる。リポジトリに
commit されたサンプル出力を判定に使うと、自分の変更が反映されない結果を測ることになる。

コールドの GET:

```text
└── resource_request uri=page://self/orders method=GET params={"order_id":"O-1"} → resource_response code=200 body_ref=000002.json durationMs=7.653
    └── get uri=page://self/orders?order_id=O-1 → cache_miss layer=resource durationMs=6.346
        ├── pre_write_cleanup uri=page://self/orders?order_id=O-1
        ├── invalidate tags=["_orders_order_id=O-1"] roPool=invalidated etagPool=invalidated cdn=skipped
        ├── cache_policy uri=page://self/orders?order_id=O-1 expiry=never resolvedTtl=31536000
        ├── save_etag uri=page://self/orders?order_id=O-1 etag="3620929378" tags=[...] requestedTtl=31536000 saved=1
        ├── save_value uri=page://self/orders?order_id=O-1 tags=[...] requestedTtl=31536000 saved=1
        └── resource_request uri=app://self/orders method=GET params={"orderId":"O-1"} → resource_response code=200
            └── get uri=app://self/orders?orderId=O-1 → cache_miss layer=resource durationMs=2.818
```

同じ URI の 2 回目:

```text
└── resource_request uri=page://self/orders method=GET params={"order_id":"O-1"} → resource_response code=200 durationMs=0.93
    └── get uri=page://self/orders?order_id=O-1 → cache_hit layer=resource durationMs=0.296
```

木の文法は 3 つだけ:

- **`A → B`** — スコープ A が型 B で閉じた。**答えを運ぶのは閉じた型**(`get` を閉じるのが `cache_hit` か
  `cache_miss` か)。`vendor/bin/stree` はこの型を出さないので、判定には `tree.php` を使う
- **入れ子** — 外側の実行中に内側が起きた。**入れ子は依存の証拠ではない**(§4)
- **葉** — そのスコープの中で起きた出来事。`save_*` `invalidate` `cache_policy` など

`--full` 相当の生 JSON が要るなら `php -r` ではなく `vendor/bin/stree --json <file>`。

### ファイルが無い現場もある

テストやオラクルは `DevQueryRepositoryLogModule` を使わず、`SemanticLoggerInterface` `#[CacheLog]` に
`SafeSemanticLogger`(sink 無し)を束縛して、自分で `flush()` を受け取るのが普通だ。この形では
`var/log` に何も作られない。観測する側が sink になるので、読み方はファイルではなく戻り値:

```php
$logger = $injector->getInstance(SemanticLoggerInterface::class, CacheLog::class);
$resource->get($uri);                       // 操作
$log = $logger->flush();                    // その場で受け取る(以降は次のセッション)
```

`flush()` は 1 回で使い切る。操作ごとに呼べばステップ単位で見られるが、**呼んだ後に前のイベントは
残らない**。`latest.json` を探して「ログが無い」と結論する前に、対象のテストやスクリプトが自分で
束縛していないかを読む。

### 木で答えられる問い

| 問い | 見るところ |
|---|---|
| キャッシュは効いたか | `get` を閉じる型。`cache_hit` / `cache_miss` |
| どの層で効いたか | 閉じた文脈の `layer`: `resource`(値) / `donut` / `donut-view` / `etag`(304 判定) |
| 保存されたか、何秒、どのキーで | `save_value` / `save_view` / `save_etag` / `save_donut*` の `tags` `requestedTtl` `saved` |
| なぜ保存されていないか | `put_skipped` の `reason`(`etag-present` / `error-code` / `not-cacheable`) |
| CDN に何を何秒キャッシュさせたか | `cdn_headers` の `headers`(応答の literal ヘッダ)。lifetime ヘッダが無い = 応答が CDN に寿命を指示しなかった(`putDonut` の種類)。エッジが実際に何を保持したかはこのログの外にある。長すぎるときは **`sMaxAge` 未指定の既定値**(generic 10 秒 / Fastly・Akamai 31536000 秒)を疑う — setter の既定値もここに literal で出る |
| エッジの再検証(304)はヒットしたか | `conditional_request` スコープの閉じ方。`cache_hit{layer: etag}` = リソースを走らせず 304、`cache_miss{layer: etag}` = 検証子が古い |
| purge は何を消したか | `invalidate` の `tags` `roPool` `etagPool` `cdn`。`cdn` は `purged`/`failed`/`skipped` の三値、fail-closed |
| miss は空だったのか、店が読めなかったのか | 同じスコープに `cache_error{operation: read}` があれば縮退。無ければコールド |
| 誰が書いたか | `command` スコープの `source`。直接呼び出しは `manual_store` / `manual_purge` / `manual_invalidate` |
| 依存の伝播 | `save_*` の `tags` ↔ `invalidate` の `tags` の突き合わせ。`depends_on` を出すのは `#[Cacheable]` の親だけで、donut の親は出さない(§4) |
| 何回リソースが走ったか | `resource_request` の数。N+1 は同じ URI の兄弟が並ぶ形で出る |
| どこで時間を使ったか | `durationMs`。親から子を引いた残りがその層の自前のコスト |
| 何を返したか | `body_ref` のファイル(`var/log/<context>/es-bodies/`) |

読み間違えやすい 4 点(いずれも実測):

1. **コールドの `get` にも `pre_write_cleanup` → `invalidate` が出る。** 保存の前に自分の古いエントリを
   消しているだけで、無効化ではない
2. **`cache_hit` で閉じたスコープの `put_skipped` は正常**(既に保存済みのものを再保存しない)。
   欠陥として読むのは `cache_miss` で閉じたスコープに出たときだけ
3. **葉の `cache_miss` と、スコープを閉じる `cache_miss` は別。** donut は内側の層でも葉を出す。
   「miss のイベントがある」は「そのスコープが miss で閉じた」ではない
4. **`#[Cacheable]` は自分の書き込みでは落ちるが、他所の書き込みでは落ちない。** 他のリソースが自分の
   タグを announce しない限り、TTL が唯一の eviction になる

## 4. 照合 — 宣言した意図と実際

宣言は意図の表明でしかない。**効いている証拠はイベント列**。下表は BEAR.QueryRepository `1.x`(`5abe573`)で
`demo/run*.php` と `tests/Fake/fake-app/src/Resource/Page/Mx` の fixture を実行して得た列 =
**その版で期待される形**。手元の出力がこれと違えば、それが所見であって表の誤りではない。

| 宣言 | コールド read | 2 回目 | 自分への書き込み |
|---|---|---|---|
| `#[Cacheable]` | close `cache_miss`。`cache_policy{expiry, resolvedTtl}` → `save_etag` → `save_value` | close `cache_hit`、イベント 0 件 | `command{source: CommandInterceptor}` に `purge` → `invalidate`、続いて**入れ子の `get` で再生成**(`RefreshSameCommand`)。close は `command_result` |
| `#[CacheableResponse]` | close `cache_miss{layer: donut}`。`put_donut` → `cdn_headers` → **`save_etag`** → `save_donut_view` → `save_donut`(全体を保存し ETag を持つ = 304 が返る) | close `cache_hit`、イベント 0 件。埋め込んだ子が無効化された回だけ `refresh_donut` → `save_etag` → `save_donut_view` | `#[Purge]`/`#[Refresh]`/`#[RefreshCache]` のいずれでも `command_result [purge, invalidate]`、次の read は `cache_miss`。**1.16.2 以前は `#[RefreshCache]` だけ書き込みが実行されない**(下の注) |
| `#[DonutCache]` | close `cache_miss`。`put_donut` → `cdn_headers` → `save_donut`。**`save_etag` は出ない** — 全体を保存しないので ETag も無く、304 は返らない | close `cache_hit` + `refresh_donut` → `put_skipped{not-cacheable}`。毎回 donut を組み直すのが正常 | 書き込み側に `#[Purge]`/`#[Refresh]`/`#[RefreshCache]` を書かないと、`command` も `invalidate` も出ない |
| `#[Refresh]` / `#[Purge]` | — | — | `command` スコープに `purge` + `invalidate`。`pre_write_cleanup` 隣接**だけ**なら誰にも告げていない |
| `#[HttpCache]` | `cdn_headers` に literal ヘッダ | 同じ | — |

**1.16.2 以前は、書き込みメソッドに `#[RefreshCache]` を付けるとキャッシュが温まっている間その書き込みが
実行されない。** `DonutCacheModule` が `#[RefreshCache]` に **query 用の `DonutCacheInterceptor`** を束縛し、
それは `donutRepository->get()` が当たった時点で `proceed()` を呼ばずにキャッシュ済み表現を返す(HTTP
メソッドを見ていない)。実測: 先に GET してから DELETE すると `onDelete` の本体は **0 回**、応答は宣言した
204 ではなく **200 + キャッシュ済みの表現**。GET せずに DELETE すれば 1 回走る。

[#215](https://github.com/bearsunday/BEAR.QueryRepository/pull/215) で修正済み(次のリリースに入る)。
古い版を使っているなら書き込みは `#[Purge]` / `#[Refresh]` で宣言する。なお `#[Cacheable]` クラスでは
`#[RefreshCache]` は何も足さない — `CommandInterceptor` が既に purge して再生成する。

食い違いの読み方: **期待した `save_*` が無い**なら宣言が届いていない(§2 の「PASS でも木が薄いとき」を
先に潰す)。**`invalidate` があるのに `cache_miss` にならない**ならタグが交わっていない(下記)。

### 依存が効いているかを確かめる手順

「親が子を埋め込んでいるから、子を purge すれば親も落ちる」は**コードを読んでも分からない**。
そのうえ**親の宣言ごとに、記録される依存も purge 後の形も違う**:

| 親の宣言 | 依存の記録 | 子を purge した後の親の read |
|---|---|---|
| `#[Cacheable]` | `depends_on` + `save_etag` / `save_value` の `tags` に子の URI タグ | close `cache_miss` — 値ごと作り直す |
| `#[CacheableResponse]` | `depends_on` は**出ない**。`save_etag` / `save_donut_view` の `tags` に子の URI タグ。`save_donut` には**乗らない**(テンプレートは子の書き込みで落とさない) | close は `cache_hit{layer: donut-view}` のまま。中に `cache_hit{layer: donut}` → `refresh_donut` → `save_etag` → `save_donut_view` と、子の入れ子 `get`(`cache_miss`)が現れる。**`refresh_donut` が依存の証拠** |
| `#[DonutCache]` | **店には記録しない** — `save_donut` の `tags` は自分の URI タグと自分の宣言だけ。子の URI タグは応答の `Surrogate-Key`(`cdn_headers` の `surrogateKeys`)にだけ乗り、それで落とせるのはエッジ側だけ | purge の前後で形が変わらない。毎回 `cache_hit{layer: donut}` → `refresh_donut` → `put_skipped{not-cacheable}`。子の鮮度は子自身のキャッシュが決める |

`#[DonutCache]` で「依存が無い」と報告するのは誤り。全体を保存しないので落とすものが無く、毎回組み直す
のが設計判断だ。

手順も宣言で分かれる:

1. 親をコールドで読む。**その宣言が使う `save_*`** の `tags` に子の URI タグが入っていることを見る
   (`#[Cacheable]` なら `save_value`、`#[CacheableResponse]` なら `save_etag` / `save_donut_view`、
   `#[DonutCache]` は `save_*` ではなく `cdn_headers` の `surrogateKeys`。
   入っていなければ `#[Embed]` ではなく値をコピーしている疑い)
2. 子の URI を purge する(`QueryRepositoryInterface::purge(new Uri(...))`)
3. 親をもう一度読む。`#[Cacheable]` は **`cache_miss` で閉じれば依存は生きている**。donut の親は
   **閉じる型が `cache_hit` のまま**で、`refresh_donut` と子の入れ子 `get` があれば生きている

`#[Cacheable]` では `depends_on` が 1 の裏付け、2→3 が実証。効果だけ(値が変わった)で判定すると、
TTL で落ちただけの場合と区別できない。

**入れ子の `get` スコープは依存の証拠にならない。** 親のスコープの中に子のスコープが現れるのは、
親の実行中に子を読んだという事実だけを言う。`ResourceInterface` を注入して手で読んでも同じ形になり、
`depends_on` もタグ伝播も起きない。見るのは入れ子ではなく、`#[Cacheable]` なら `depends_on` と `tags`、
donut の親なら `tags` と `refresh_donut`。

**`#[Embed]` を付けただけでは足りない。** 依存を登録するのは `#[Cacheable]` では
`QueryRepository::setCacheDependency()`、donut では `ResourceDonut::create()`(`DonutRequest` 経由)で、
どちらも保存時に **`$ro->body` に `AbstractRequest` のインスタンスが残っているものだけ**を辿る。埋め込んだ
値をスカラーに解決して body を作り直すと、その時点で Request は body から消えており、宣言がどれでも依存は
登録されない。body に Request(または ResourceObject)を残すか、announce 側で解決する。

`#[Cacheable]` にはもう 1 つ条件がある。`setCacheDependency()` は子を走らせたあと、子に `ETag` ヘッダが
無ければ `continue` する — 子自身にキャッシュ宣言が無く ETag を持たなければ、Request が残っていても
`depends_on` は出ない。donut では `ResourceDonut::create()` が集めた子のタグを店に残すのは
`#[CacheableResponse]`(`save_etag` / `save_donut_view`)だけで、`#[DonutCache]` は応答の `Surrogate-Key`
に載せて終わる。

### タグと TTL のどちらが要るかは、ログでは決まらない

タグだけ宣言しても、**そのタグを announce する書き込み経路がある値**しか新しく保てない。body に他所の値を
コピーしていて announce 経路が無ければ、タグは届かず TTL だけが床になる。コードに問う:

1. この body に入る値ごとに、**それを変える書き込み経路**は何か
2. その経路は invalidate を呼ぶか(呼ばないなら TTL が唯一の eviction)
3. 同じデータの**別の複製**があるなら、複製の寿命は原本以下か(長いと、原本が捨てた値を複製が返す)

3 は attribute から機械的に検査できる。実例:
[BeMart `tests/Resource/AgentCorpusCacheTest.php`](https://github.com/be-framework/BeMart/blob/1.x/tests/Resource/AgentCorpusCacheTest.php)

## 5. 切り分け — アプリの問題か、ライブラリの問題か

木は「何が起きたか」を言うが、「誰の落ち度か」は言わない。**先に観測で候補を 1 つに絞り、
それから該当行の変数を見る**。順番を逆にすると、写しを覗くだけで終わる。

| 観測 | アプリ側 | ライブラリ側 | 決め手 |
|---|---|---|---|
| 期待した `get` スコープが無い(miss すら出ない。木全体が空とは限らず、その 1 本だけ欠けることがある) | `final`(`ReflectionClass::isFinal()` が true)、属性そのものが無い、文脈が店を束縛していない | sink が arm を拒否 | 織られたか(`get_class`)→ 真なら `isFinal()` と属性の有無で二分 / `error_log` |
| 期待した `save_*` が無い | `put_skipped` の `reason` がアプリ由来(自前 ETag・非 200) | `put_skipped` も無いのに保存されない | `put_skipped` の有無と `reason` |
| `save_*` の `tags` に子が無い | 値をコピーしている(`#[Embed]` でない)、`#[Cacheable]` の子が `ETag` を持たない(子自身にキャッシュ宣言が無い)、または親が `#[DonutCache]`(店には記録しないのが仕様) | 伝播の欠陥 — `#[Cacheable]` で `depends_on` はあるのにタグが乗らない(`CacheDependency::depends()` が親の `Surrogate-Key` に子タグを積む)、`#[CacheableResponse]` で `cdn_headers` の `surrogateKeys` には子タグがあるのに `save_etag` / `save_donut_view` に乗らない | まず親の宣言。`#[Cacheable]` なら `depends_on` の有無。donut なら `save_donut` ではなく `cdn_headers` の `surrogateKeys` と `save_etag` / `save_donut_view` の `tags` を並べる: `surrogateKeys` にあって `tags` に無い = ライブラリ側、どちらにも無い = アプリ側(Request が body に残っていない) |
| 書き込みが `invalidate` を出さない | 書き込み経路に `#[Refresh]`/`#[Purge]` が無い | 属性はあるのにマッチャがそのメソッドを拾わない | 織られたオブジェクトの `bindings` にそのメソッドがあるか |
| `invalidate` は出るが親が hit のまま | タグの選び方が違う(URI タグと共有サロゲートキーの混同) | タグ集合の交差計算の欠陥 | 2 つのタグ集合を並べて交わりを見る |
| `invalidate` のタグがどのリソースの宣言とも一致しない | **手書きの `invalidateTags()` が定数からドリフトしている** — リソースは `SURROGATE_KEY` 定数を宣言し、無効化側は生文字列を持ったまま取り残された | — | `invalidate` の `tags` を、リソースが宣言する定数の実値と 1 文字ずつ突き合わせる。docblock だけ正しいことがある |
| `conditional_request` スコープが 1 つも無い | — | — | **まず要求側を見る**。`If-None-Match` を送っていない要求なら正常で、`bin/dev.php` の CLI 要求は送らない。送ったと確かめた上で無いときだけ、プロキシ・WAF・CDN が落としている |
| 値が古い | TTL が床(設計判断) | — | `save_*` の `ttl` |
| `cache_error` / `pool_error` | 店の設定・接続 | 縮退の扱い | 例外か縮退かは `docs/what-the-log-proves.md` |

判断点の在処: `CacheInterceptor`(hit/miss と保存)、`CommandInterceptor`(書き込み後の無効化)、
`QueryRepository`(put/get/purge)、`ResourceStorage`(タグと 2 プール)、`CacheDependency`(依存の伝播 —
`#[Cacheable]` の経路だけ)、`DonutRepository` / `SurrogateKeys`(donut の子タグ)、
`HttpCache` / `CliHttpCache`(304 判定)、`EtagSetter`(ETag 生成)。

**「ライブラリ側」に振った候補が 1 つ残ったときだけ**、該当クラスの分岐を観測する。道具は
`koriym/xdebug-mcp`(`composer global require koriym/xdebug-mcp`)。**php 本体に Xdebug が入っていなくてよい**
— ラッパーが自分で読み込む。**vendor は既定で記録から除外される**ので、明示して入れる:

```bash
# 1) どの分岐まで届いているか(--include-vendor が無いとライブラリのフレームは空)
xtrace --include-vendor="bear/query-repository" --context="tags absent from parent save" -- php bin/dev.php get /orders

# 2) 判断している行を名前で探す(行番号は版ごとに動く)
grep -n "SURROGATE_KEY" vendor/bear/query-repository/src/CacheDependency.php

# 3) その行で実引数を見る
xstep --break="vendor/bear/query-repository/src/CacheDependency.php:<line>" \
  --steps=20 --context="what tags arrive" -- php bin/dev.php get /orders
```

`xtrace` はスクリプトの stdout をそのまま流し、**トレース自体は `/tmp/trace.*.xt` に書いて**末尾に場所と
規模を出す(実測: 1 リクエストで 63688 行 / 534 関数)。読むのはそのファイルで、全部は読まない —
`--context` に何を見たいかを書き、ファイルは目的の関数名で `grep` する。

**`xstep` の前に、ライブラリの既存テストを 1 本走らせる。** 同じシナリオを守るテストが既に赤なら、
切り分けはそこで終わる(実測: `--filter testMultipleParentsDependOnSameChild tests/CacheDependencyTest.php`
が伝播の欠陥をそのまま赤で再現した)。スイート全体は走らせない。

**`var_dump` / `printf` を仕込まない。** 使い捨てスクリプトに同じロジックを書き写して覗くのは、対象ではなく
写しを見ている(写し間違いに気づけない)。再現スクリプトは**対象を呼ぶだけ**にする。

## 6. 報告

切り分けの結論は、次の 6 つで書く。1 つでも欠けたら、まだ報告できていない:

1. **どちらの問題か** — アプリ / ライブラリ / 設計判断のどれか
2. **観測の根拠** — 木の抜粋(`save_value{tags: [...]}` → `invalidate{tags: [...]}` の形)と、
   ライブラリを疑うなら `xstep` で見た実引数。「トレースした」と書くのは実際にツールを使ったときだけ
3. **最小再現** — 対象を呼ぶだけのスクリプトかテスト 1 本
4. **ユーザーが何を感じるか** — 「在庫が最大 60 秒古い」のように、外から見える言葉で
5. **修正の証明** — 直したなら、**どのイベントがどう変わったかを木で示す**。テストが緑になったことは
   証明ではない(テストは同じ盲点を持ち得る)
6. **既存の検証はこれを検出できたか** — テスト・オラクル・CI を実際に走らせて答える。緑だったなら
   **その検証がアプリのコードパスを通っているか**を確かめる。ライブラリの同じ能力を直接叩いて
   代替検証しているだけなら、アプリ側の誤りは原理的に見えない(実例: オラクルが `purgeTags` を
   `ResourceStorageInterface::invalidateTags()` に直接渡すと、アプリの無効化サービスを一度も通らない)

**ライブラリだと言うなら、ライブラリの fixture で落ちるテストを添える。添えられないなら、
まだアプリ側の疑いに戻す。**

## 範囲外

観測文脈は開発用だ。プールはファイルで、body は全部ディスクに落ち、記録は無条件。本番で同じことを
したいなら `ProdQueryRepositoryLogModule`(サンプリングと flush 時の判定を持つ)を見る。
セッションは URI(クエリ文字列込み)・client validator・生の例外文を含むので、本番の出力先に何を流すかは
先に決める(scrub や流量制限は `LogWriterInterface` を decorate する)。

CDN 側の実挙動(伝播遅延・エッジ側 eviction)、`#[HttpCache]` の静的ヘッダ設定、独自ヘッダ名のカスタム
setter、実時間での期限消滅は、どちらの文脈のログにも出ない。詳細は `what-the-log-proves.md` の
"What the log does not record"。
