# Shopping / CheckoutEntry

購入フローでは、カートからの入口と注文手続き画面を分けて扱う。

## CheckoutEntry

`CheckoutEntry` はカートから購入手続きへ入るゲートウェイ状態である。多くのEC実装では `/shopping` でログイン状態を判定するが、ALPS 上の rel は `goCheckoutEntry` として定義する。

`goCheckoutEntry` は匿名ユーザーにも提示できる。状態によって表現に現れる次の rel が変わる。

- 未ログイン: `goShoppingLogin`, `goShoppingNonMember`
- ログイン済み: `goShopping`
- 戻る導線: `goCart`

rel 自体の意味は状態に依存させない。例えば `goShopping` を未ログイン時だけ `/shopping/login` に読み替えるのではなく、未ログイン時には `goShoppingLogin` / `goShoppingNonMember` を提示する。

## Shopping

`Shopping` は注文手続き画面である。配送先、支払方法、配送時間帯、注文メッセージを確認・入力し、`doConfirmOrder` へ進む。注文確認後の確定操作が `doCheckout` である。

`Shopping` はログイン済み会員、または非会員購入情報の送信などで購入者情報が確定したフローを前提とする。匿名カートから直接 `goShopping` を提示しない。

## HTML 実装の期待挙動

HTML ブラウザで `/shopping` に到達したとき、未ログインなら JSON 401 を表示して終わらせない。`CheckoutEntry` としてログイン/ゲスト購入導線へリダイレクトまたは表示する。

ブラウザテストは以下を機能仕様として確認する。

1. 匿名で商品をカートに追加できる。
2. 匿名でカート数量を増減できる。
3. 匿名でカート明細を削除できる。
4. 匿名で「レジに進む」を押しても JSON 401 が裸で表示されず、購入ログイン/ゲスト購入導線に進む。
