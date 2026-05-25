# HTML Route Coverage Baseline

作成日: 2026-05-26
対象: BeMart HTML front controller / Twig route helpers / admin route coverage

## 目的

BeMart の HTML 画面で、テンプレートから参照される route が 404 / 405 / Fatal / 未実装表示に落ちない状態を維持する。
EC-CUBE 由来テンプレートは route 名でリンクを生成するため、`RouteTable` を front controller と Twig helper の単一 source of truth とする。

## 方針

- HTML で公開する HTTP method は **GET / POST のみ**。
- ブラウザ向け HTML には `PUT` / `DELETE` / `data-method="put"` / `data-method="delete"` を出さない。
- BEAR Resource の `onPut()` / `onDelete()` は残す。
- 破壊的操作や更新操作は **HTML POST → Router の `dispatchMethod` → 内部 Resource PUT/DELETE** に変換する。
- route name は EC-CUBE テンプレート互換のため維持する。
- route の query/form param 名と Resource param 名が違う場合は `Route::$queryParamMap` で明示的に変換する。
- 未実装 placeholder に逃がすのではなく、既存 Resource か安全な redirect/no-op Resource に接続する。

## 完了 baseline

2026-05-26 時点で次を満たす。

- `RouteTable` 内の `unsupported-route`: 0
- `RouteTable` dispatch 欠落: 0
- `var/templates/**/*.twig` の `url()` / `path()` route 欠落: 0
- HTML 内の `PUT` / `DELETE` method 参照: 0
- 通常画面の「未実装」表示: 0
- ローカルリンククロール結果:
  - visited pages: 158
  - discovered local links: 158
  - link problems: 0
  - page problems: 0
  - auth responses: 0

## 主要実装ポイント

- `~/git/be-bemart/src/Router/Route.php`
  - `dispatchMethod`
  - `queryParamMap`
- `~/git/be-bemart/src/Router/MatchedRoute.php`
  - dispatch method と query param map を保持
- `~/git/be-bemart/src/Bootstrap.php`
  - HTTP method ではなく matched route の `dispatchMethod` で Resource を呼ぶ
  - wire alias と route alias を Resource param に正規化
  - `BadRequestException` を HTTP response に変換し、HTML 上の raw Fatal を避ける
  - CSV/PDF 等の download response は Twig render せず body を返す
- `~/git/be-bemart/src/Router/RouteTable.php`
  - admin alias route を補完
  - HTML 公開 method を GET/POST に限定
- `~/git/be-bemart/src/Resource/Page/ActionRedirect.php`
- `~/git/be-bemart/src/Resource/Page/Admin/ActionRedirect.php`
  - GET で来た action route や no-op POST の安全な戻り先

## 検証コマンド

```bash
vendor/bin/phpunit --filter 'RouterTest|TemplateRouteCoverageTest|CsrfProtectionCoverageTest' --colors=never
vendor/bin/phpunit tests/Resource --filter HtmlRender --colors=never
composer test:http -- --colors=never
composer test:fake -- --colors=never
composer psalm -- --output-format=console
```

追加のリンククロールでは、ログイン済み storefront/admin セッションで全ローカル `<a href>` を巡回し、次を問題として扱う。

- 404
- 405
- 5xx
- `Fatal error`
- `Uncaught`
- `Method Not Allowed`
- `Not Found`
- `Unknown APP_CONTEXT`
- `未実装`
- `実装していません`

## 今後の注意

- 新しい Twig route を追加したら、必ず `RouteTable` に route name を追加する。
- 更新/削除リンクを `<a>` で追加しない。POST form にする。
- `PUT` / `DELETE` を HTML に露出させない。
- Resource 側の `onPut()` / `onDelete()` を消す必要はない。HTML 入口だけ GET/POST にする。
- placeholder route を再導入しない。未完成機能は安全な Resource へ接続し、画面上に raw Fatal を出さない。
