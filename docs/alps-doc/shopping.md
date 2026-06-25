---
layout: default
title: "Shopping / CheckoutEntry"
---

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

`Shopping` は注文手続き画面である。配送先、支払方法、配送方法（配送業者・お届け希望日・配送時間帯）、注文メッセージを確認・入力し、`doConfirmOrder` へ進む。注文確認後の確定操作が `doCheckout` である。

配送方法の選択肢は `deliveryOptions`（選択可能な配送方法の一覧。各要素が `deliveryId` / `deliveryMethodName` / `deliveryFee` と、選べる `deliveryTime` の集合・`deliveryDate` の候補を持つ）として `Shopping` 状態に提示する。これは支払方法（`paymentMethods`）と同型のアフォーダンスであり、`visible` な `dtb_delivery` + `dtb_delivery_time` + `dtb_delivery_fee` から構築して body へ供給し、HTML 層が `配送方法` / `お届け日` / `お届け時間` の `<select>` を描画する。`doConfirmOrder` は選択された `deliveryId` / `deliveryDate` / `deliveryTime` を受理し、`deliveryFeeTotal`（送料）を確定して `ShoppingConfirm` に反映する。`deliveryOptions` が空のとき配送方法は提示できない。

`Shopping` はログイン済み会員、または非会員購入情報の送信などで購入者情報が確定したフローを前提とする。匿名カートから直接 `goShopping` を提示しない。

## HTML 実装の期待挙動

HTML ブラウザで `/shopping` に到達したとき、未ログインなら JSON 401 を表示して終わらせない。`CheckoutEntry` としてログイン/ゲスト購入導線へリダイレクトまたは表示する。

ブラウザテストは以下を機能仕様として確認する。

1. 匿名で商品をカートに追加できる。
2. 匿名でカート数量を増減できる。
3. 匿名でカート明細を削除できる。
4. 匿名で「レジに進む」を押しても JSON 401 が裸で表示されず、購入ログイン/ゲスト購入導線に進む。
