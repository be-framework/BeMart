# 20260611-form-negative-visible-ui-regression Web+DB 全ルート検証結果


## Summary

- context: `html-eccube-sql-hal-app`
- baseUrl: `http://localhost:8080`
- DB: `eccubedb_test` (`DATABASE_URL`)
- Fake JSON / Fake context / 直接DB seed: **未使用前提**。runner は Web/HTTP 境界のみを操作し、SQL fixture は投入しない。
- Feature matrix: pass 0 / fail 0 / 対象外 0
- OpenAPI operations: pass 0 / fail 0 / 対象外 0 / total 0
- NG cases: pass 19 / fail 0 / total 19
- screenshots: `docs/web-e2e/screenshots/20260611-form-negative-visible-ui-regression/`
- results JSON: `docs/web-e2e/results/20260611-form-negative-visible-ui-regression.json`

## Scope

- 母集団は `docs/api/openapi.json` の 0 operations と `docs/web-e2e/feature-implementation-matrix.md` の 0 features。
- 画面 feature は matrix の順序で実ブラウザ到達、最終URL、HTTP status、title、h1、主要テキスト、form一覧、screenshotを保存した。
- CSV/PDF/unsafe operation など画面だけで完結しない OpenAPI operation は、feature row に紐づくものは matrix coverage、未紐づきのものは同一 browser context の HTTP probe として記録した。
- Web で前提データを作れないもの、未ログイン/管理者未作成で到達できないものは `fail` として記録した。

## Setup Evidence

- 管理ログイン: fail final=``
- 会員登録: fail final=``
- 業務状態作成: fail product=`` memberOrder=`` nonMemberOrder=``


## Known Failures

- なし

## New Failures

- なし


## OpenAPI Operation Failures

- なし


## Negative Case Failures

- なし

## Negative Cases

- ✔ pass 会員登録 必須欠落: POST /entry, status=400, visibleErrorUi=6/1, final=`http://localhost:8080/entry`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-entry-required-missing.png`
- ✔ pass 会員登録 メール形式不正/確認不一致: POST /entry, status=400, visibleErrorUi=2/1, final=`http://localhost:8080/entry`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-entry-invalid-email-mismatch.png`
- ✔ pass 会員登録 CSRF欠落: POST /entry, status=403, visibleErrorUi=0/0, final=`http://localhost:8080/entry`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-entry-csrf-missing.png`
- ✔ pass ログイン 認証失敗: POST /login, status=401, visibleErrorUi=1/1, final=`http://localhost:8080/login`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-login-wrong-credential.png`
- ✔ pass ログイン 形式不正: POST /login, status=400, visibleErrorUi=0/0, final=`http://localhost:8080/login`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-login-invalid-email.png`
- ✔ pass パスワード再発行 メール形式不正: POST /forgot-password, status=403, visibleErrorUi=0/0, final=`http://localhost:8080/forgot-password`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-forgot-password-invalid-email.png`
- ✔ pass パスワードリセット 不正キー: POST /reset, status=403, visibleErrorUi=0/0, final=`http://localhost:8080/reset`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-reset-invalid-key.png`
- ✔ pass お問い合わせ 必須欠落: POST /contact, status=400, visibleErrorUi=4/1, final=`http://localhost:8080/contact`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-contact-required-missing.png`
- ✔ pass お問い合わせ 形式不正/境界超過: POST /contact, status=400, visibleErrorUi=0/0, final=`http://localhost:8080/contact`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-contact-invalid-email-long-body.png`
- ✔ pass カート投入 数量境界不正: POST /cart/item, status=403, visibleErrorUi=0/0, final=`http://localhost:8080/cart/item`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-cart-item-invalid-quantity.png`
- ✔ pass 非会員購入 必須欠落: POST /shopping/non-member, status=400, visibleErrorUi=11/1, final=`http://localhost:8080/shopping/non-member`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-shopping-non-member-required-missing.png`
- ✔ pass 購入確定 存在しない preOrderId: POST /shopping/checkout, status=403, visibleErrorUi=0/0, final=`http://localhost:8080/shopping/checkout`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-shopping-checkout-nonexistent-preorder.png`
- ✔ pass 会員情報変更 未ログイン: POST /mypage/change, status=403, visibleErrorUi=0/0, final=`http://localhost:8080/mypage/change`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-mypage-change-unauthenticated.png`
- ✔ pass お届け先編集 存在しないID/未ログイン: PUT /mypage/address, status=400, visibleErrorUi=0/0, final=`http://localhost:8080/mypage/address`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-mypage-address-nonexistent-id.png`
- ✔ pass 管理ログイン 認証失敗: POST /admin/login, status=401, visibleErrorUi=1/1, final=`http://localhost:8080/admin/login`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-admin-login-wrong-credential.png`
- ✔ pass 管理ログイン CSRF不一致: POST /admin/login, status=403, visibleErrorUi=0/0, final=`http://localhost:8080/admin/login`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-admin-login-csrf-invalid.png`
- ✔ pass 管理2FA チャレンジなし: POST /admin/two-factor-auth, status=403, visibleErrorUi=0/0, final=`http://localhost:8080/admin/two-factor-auth`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-admin-two-factor-no-challenge.png`
- ✔ pass 管理商品 未ログインPOST: POST /admin/product, status=400, visibleErrorUi=0/0, final=`http://localhost:8080/admin/product`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-admin-product-unauthenticated.png`
- ✔ pass 管理CSVアップロード 未ログイン: POST /admin/product-csv, status=400, visibleErrorUi=0/0, final=`http://localhost:8080/admin/product-csv`, screenshot=`screenshots/20260611-form-negative-visible-ui-regression/negative/ng-admin-csv-upload-unauthenticated.png`

## Boundaries

- 外部決済、実SMTP、本番運用ファイル破壊操作は fake/noop または HTTP 境界確認に留める。
- 管理者アカウントや商品・注文などの dtb_* 業務データは runner では直接 SQL seed しない。Web で作成できない場合は該当 feature/operation を fail とする。
- `注文履歴詳細` / `再注文` は既存 known fail として、今回 run でも前提注文作成可否を結果に残す。
