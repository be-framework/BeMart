# HTML画面移植マトリクス（2026-05-26）

EC-CUBE 4.3 の routable な storefront/admin 画面と、画面から参照される route/action の現状です。

> 詳細な **EC-CUBE route / 対応 ALPS ID / 実装状態** は、生成済みHTML
> [`docs/eccube-feature-alps-status.html`](eccube-feature-alps-status.html) を参照してください。

## 現在の判定基準

- `alps.json` が意味論の source of truth。
- `RouteTable` が EC-CUBE route name / URL / HTTP method / Resource URI を持つ。
- `AlpsRouteMap` が RouteTable の各 route method を ALPS descriptor に接続する。
- HTML の公開HTTP methodは GET / POST のみ。PUT / DELETE は出力しない。
- `unsupported-route` は使わない。
- 完全な業務処理がまだ無い mutation は `ActionRedirect` で安全に受け、ALPS上の戻り状態または操作として追跡する。

## 集計

| 項目 | 現状 |
|---|---:|
| EC-CUBE route names | 165 |
| RouteTable entries | 236 |
| RouteTable method entries | 270 |
| ALPS mapped method entries | 270 / 270 |
| concrete Resource dispatch | 215 |
| `ActionRedirect` partial dispatch | 55 |
| `unsupported-route` | 0 |
| HTML `method="put/delete"` / `data-method="put/delete"` | 0 |
| HTTP crawl baseline | 238 pages / 0 problems (`composer test:http`) |

## 実装状態の意味

| 状態 | 意味 |
|---|---|
| 実装済み | RouteTable から具体 Resource URI に到達する。 |
| 部分 / 安全退避 | ALPS ID は存在し、HTML route は壊れていない。ただし Resource は `page://self/action-redirect` で、安全な戻り遷移または no-op として受けている。完全な業務処理は後続タスク。 |

## 主要導線ステータス

| ロール | 導線 | 状態 | 残差 / 次タスク |
|---|---|---|---|
| Storefront | トップ → 商品一覧 → 商品詳細 | 実装済み | SEO JSON-LD等は残差。 |
| Storefront | 商品詳細 → カート投入 → カート | 実装済み | HTMLはGET/POSTのみ。 |
| Storefront | カート → 購入手続き → 完了/エラー | 実装済み | `doCreateOrder` はPhase-A由来のfunctional stub。 |
| Storefront | 会員登録 / ログイン / パスワード再発行 / 問い合わせ | 実装済み | Contact body enrichmentは残差。 |
| MyPage | 注文履歴 / 会員情報変更 / 配送先 / お気に入り | 実装済み | dashboard / favorite / address body enrichmentは残差。 |
| Admin | ログイン / ダッシュボード / 商品 / 受注 / 会員 / コンテンツ / 設定 | 実装済み | CSV/PDF/一部mutationはfunctional stubまたはActionRedirect partial。 |
| Admin Store/Plugin | plugin list / template list/add | 部分 | 外部Owners Store実通信とplugin install/search subtreeは移植対象外。 |

## 詳細リスト

詳細はHTML版に集約します。

- [`eccube-feature-alps-status.html`](eccube-feature-alps-status.html)
- 生成コマンド: `php bin/generate-eccube-feature-alps-status.php`

このMarkdownは方針と集計だけを持ち、route単位の表は生成物側を正とします。
