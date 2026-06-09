# Cart

`Cart` は現在の購入セッションに紐づくカート内容を表示する状態である。匿名ユーザーでも到達でき、商品追加・数量変更・削除は現在のカートに対する操作として許可される。

## 匿名カート

カートはログイン状態を前提にしない。`doAddCartItem`、`doUpdateCartItemQuantity`、`doRemoveCartItem` は、会員カートか匿名セッションカートかを rel の意味として分けない。どちらの場合も「現在のカート」を対象にし、所有者の解決はサーバー側のセッション境界で行う。

したがって、未ログインの商品詳細に「カートに入れる」が表示されることは仕様である。カート操作でブラウザに 401/404 JSON が裸で表示される場合は、ALPS 仕様ではなく HTML form / session / route 接続の欠陥として扱う。

## 購入手続き入口

`Cart` からの primary CTA は `goCheckoutEntry` である。`goShopping` へ直接進むのではなく、購入手続き入口でログイン状態を判定する。

- 匿名ユーザー: `goShoppingLogin` または `goShoppingNonMember` を選ぶ。
- ログイン済みユーザー: `goShopping` に進む。

実装URIは `/shopping` でよいが、rel の意味は `goCheckoutEntry` として扱う。`goShopping` はログイン済み、または購入者情報が確定した注文手続き画面を表す。
