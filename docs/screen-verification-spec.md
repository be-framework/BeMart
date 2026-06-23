---
layout: default
title: "Screen Verification Spec — BeMart"
---

# 画面単位 検証仕様 (Screen Verification Spec)

> 各画面・各操作について、**実フォームが送る値（既定値込み）** と **期待される観測可能な結果**（遷移先 / 成功・エラーメッセージ / DOM変化）を定義する。緑のユニット/フローテストは合成パラメータでリソースメソッドを叩くだけで「画面がユーザーにとって動く」ことを証明しない — その穴を塞ぐ第4の足。`現リスク` 列は**現実装が期待観測を満たさない可能性**（=要修正候補）をコード精読から flag したもので、実機実行で確定させる。

> 生成: docs/eccube-spec-coverage の screen-verification-spec ワークフロー（11群並列, 各画面のリソース/テンプレ/EC-CUBE ソース精読）。


## サマリ

- 画面: **91**　検証項目: **218**　現リスク flag: **163**

## 各画面


### storefront-public


#### 商品一覧 (Page/Products) — var/templates/Page/Products.html.twig

`/products` ／ src/Resource/Page/Products.php (onGet)　<br>前提: anonymous; fake product corpus loaded (seed-dev). This is the screen the top-page "全ての商品" / header search lands on — the exact flow whose empty-category default previously 400'd.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| ページ初期表示（全商品一覧） | GET /products with no query params (the top-page "全ての商品" link target) | category_id=(なし), name=(なし), pageno=(なし), disp_number=(なし), orderby=(なし) | 200 OK; 商品グリッドが描画され、各商品カード（商品名・price02・画像）が並ぶ。totalItemCount>0。 | none — onGet defaults keyword=null→productQuery->list(500,0), category_id=null skips filterByCategory. This is the fixed path. |
| 絞り込みフィルタ保持フォーム #form1（送信ボタンなし） | GET /products from the header search with the EC-CUBE empty-category default (the original 400 bug) | category_id=(空), name=(空), pageno=1, disp_number=20, orderby=(空) | 200 OK; 全商品が再描画される（空のcategory_idで絞り込みされない）。NOT a 400. | none now — get-products.param.json category_id pattern is ^[0-9]*$ minLength:0, so "" passes the JsonSchema; onGet treats category_id===null/'' as no filter. Regression-guard this exact row: it is the bug that triggered this whole spec. |
| カテゴリ絞り込み（パンくず/カテゴリリンク経由） | GET /products?category_id=1 | category_id=1, name=(空), pageno=1, disp_number=20, orderby=(空) | 200 OK; 「ジェラート」カテゴリの商品のみに絞り込まれて再描画。パンくずに categoryName 表示。 | none — categoryName('1')='ジェラート', filterByCategory matches on categoryNames. But categoryName map is HARDCODED 1..6; an unknown category_id (e.g. 99) silently returns ALL products (no filter, no error) — flag if faithful behavior should be empty/404. |
| 表示件数セレクト (.ec-select / #disp_number, JS駆動) | select 40件 from the disp_number dropdown | JS sets #disp_number=40, #pageno=1, then submits #form1 → GET /products?disp_number=40&... | list re-renders with up to 40 items per page; pager recomputed. | ⚠️ JS-ONLY: #form1 has NO submit button; the select's jQuery .change handler ($('#disp_number').val()+submit) is the only trigger. With JS disabled, changing the dropdown does nothing — no-JS user cannot change page size. Flag as a no-JS dead control. |
| 並び順セレクト (.order-by, JS駆動) | select 価格が低い順 from the orderby dropdown | JS sets #orderby=price_low, #pageno=1, submits #form1 → GET /products?orderby=price_low&... | list re-renders sorted by price02 ascending. | ⚠️ JS-ONLY (same as disp_number): no submit button, relies on jQuery .change. No-JS submit does nothing. Also note the rendered <select> has no name attribute and is NOT inside #form1 — only the hidden #orderby mirror is submitted, so server never sees the visible select directly. |
| ページャ 前へ/次へ/番号 (.ec-pager a[href]) | click 次へ | GET ?category_id=&name=&disp_number=20&orderby=&pageno=2 (plain anchor href) | 200 OK; page 2 of the list renders; current page indicator moves to 2. | none for no-JS (plain <a href>). Minor: href interpolates raw filters.category_id/name unescaped into the query string; if name contains & or spaces the link could break — flag for keywords with special chars. |

#### 商品詳細 (Page/Product) — var/templates/Page/Product.html.twig

`/product/{productCode}` ／ src/Resource/Page/Product.php (onGet) → POST target src/Resource/Page/Cart/Item.php (onPost)　<br>前提: anonymous; product with the given productCode exists and is visible in the fake corpus. Cart bound to sessionPrefix cookie.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| ページ初期表示（商品詳細） | GET /product/<existing productCode> | productCode (path param) | 200 OK; 商品名・price02・在庫状態・説明が描画され、在庫ありなら「カートに入れる」フォーム、在庫なしなら出品なしボタンが出る。 | ⚠️ ProductNotFoundException is NOT caught in onGet — an unknown/invalid productCode throws and the user gets a framework error page instead of a 404 "商品が見つかりません". Flag: test a bogus productCode. |
| カートに入れる（数量入力 + 送信ボタン） | set quantity then submit the add-to-cart form | operation=add, quantity=1 (AddCartForm default), productCode=<seeded>, csrfToken=<issued> | 303 SEE OTHER → Location: /cart ; browser follows to the cart page where the just-added line item appears and the header cart badge shows the new count. | ⚠️ Header cart badge: known-hardcoded-0 class of bug — verify the badge on the LANDING /cart (and global header) actually reflects the new quantity, not a static 0. Cart/Item onPost(add) sets Code::CREATED then redirectToCartOnSuccess()→303 Location /cart, so the redirect itself is correct; the badge is the risk to assert. |
| カートに入れる — 数量0/空での送信 | submit add-to-cart with quantity cleared | operation=add, quantity=(空→null), productCode=<seeded>, csrfToken=<issued> | field/validation error "数量を入力してください" 等が商品詳細ページ上に表示される（再描画）。 | ⚠️ Cart/Item onPost: quantity===null → missingQuantity() returns 400 with JSON body {message:'Invalid input.'} and NO redirect. In html context this likely renders a bare error page, not the product page with an inline quantity error — no EC-CUBE-faithful inline field error. Flag. |
| カートに入れる — 在庫超過 | submit add-to-cart with quantity greater than stock | operation=add, quantity=<stock+1>, productCode=<seeded>, csrfToken=<issued> | 在庫切れ/数量調整メッセージが表示される、もしくは adjustedQuantity に丸められてカートに追加され /cart で丸め後数量が見える。 | ⚠️ OutOfStockException path: Cart/Item onPost(add) does not wrap becoming() in try/catch for OutOfStock — if the domain throws, the user gets an error page rather than an EC-CUBE-style 在庫不足 message. Verify what the becoming chain actually does (clamp vs throw). |

#### カート (Page/Cart) — var/templates/Page/Cart.html.twig

`/cart` ／ src/Resource/Page/Cart.php (onGet) → line forms POST to src/Resource/Page/Cart/Item.php　<br>前提: anonymous; cart bound to sessionPrefix cookie; at least one item already in the cart for the mutation rows.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| ページ初期表示（カート一覧） | GET /cart | (none; cart resolved from sessionPrefix cookie) | 200 OK; カート内の各明細（商品名・数量・price・小計）と totalPrice / deliveryFeeTotal が描画される。空なら空カート表示。 | none for the page render. But the GLOBAL header cart-count badge is the previously-hardcoded-0 defect surface — assert the badge equals cartCount here. |
| 数量アップ（+ ボタン） | click the quantity-up button on a line item | productCode={{item.productCode}}, operation=up, quantity={{item.quantity + 1}} | 303 SEE OTHER → /cart ; the line's quantity is now +1 and the cart total recomputed on the re-rendered cart page; header badge updates. | ⚠️ Cart/Item onPost routes operation=up → onPut → redirectToCartOnSuccess (303 /cart). Quantity is computed in the TEMPLATE as item.quantity+1 at render time; if the post fails partway the badge/total must reflect actual state, not the optimistic value. Verify the re-rendered /cart shows the true new quantity. |
| 数量ダウン（- ボタン） | click the quantity-down button on a line item with quantity>1 | productCode={{item.productCode}}, operation=down, quantity={{item.quantity - 1}} | 303 → /cart ; line quantity -1, total recomputed; header badge updates. | ⚠️ When item.quantity is 1, the template emits quantity=0 (1-1). onPut with quantity=0 — verify the domain either removes the line or rejects with a faithful message; a silent 0-quantity line or an uncaught exception would be a defect. Flag the quantity==1 → down case explicitly. |
| 削除（× / 削除ボタン） | click delete on a line item | productCode={{item.productCode}}, operation=remove | 303 → /cart ; that row disappears from the cart list; total recomputed; header badge decremented. | ⚠️ Cart/Item onPost(remove) → onDelete → redirectToCartOnSuccess (303 /cart). onDelete is idempotent; removing a non-present code still returns a Final (no error) — acceptable. Assert the ROW actually disappears, not just a 303. |

#### ログイン (Page/Login) — var/templates/Page/Login.html.twig

`/login` ／ src/Resource/Page/Login.php (onGet/onPost)　<br>前提: anonymous. Registered customer login-test@example.com / local-dev-member-password exists in fake corpus for the success row.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| ログインボタン — 正常ログイン | submit the login form with valid credentials | mode=login, email=login-test@example.com, password=local-dev-member-password (PoC-prefilled by onGet) | 303 SEE OTHER → Location: /mypage ; browser lands on My Page as the authenticated customer (session written). | none — mode=login present → browserForm=true; on success sessionWriter->authenticate + Code::SEE_OTHER + Location /mypage. NOTE: onGet PRE-FILLS real credentials (POC_LOGIN_* constants) into the visible password field — a security/hygiene defect for any non-dev run; flag that the prefill must be removed before prod. |
| ログインボタン — 認証失敗 | submit with a wrong password | mode=login, email=login-test@example.com, password=wrong | 401 with the login page re-rendered; inline error "メールアドレスまたはパスワードが正しくありません。" shown in ec-errorMessage; email field repopulated, password blank. | none — LoginFailedException caught (browserForm) → rejectForm(Code::UNAUTHORIZED), template renders {{ message }} and form.error('email'). Verify the 401 status still renders the HTML page (not a bare error). |
| ログインボタン — 空入力 | submit with both fields cleared | mode=login, email=(空), password=(空) | 400; login page re-renders with inline "入力してください。" on email and password. | none — formErrors() catches empty before becoming; rejectForm(400). param schema minLength:0 lets the empty values through to the resource (good — error is field-level, not a transport 400). |

#### 会員登録 (Page/Entry) — var/templates/Page/Entry.html.twig

`/entry` ／ src/Resource/Page/Entry.php (onGet/onPost)　<br>前提: anonymous; no existing customer with the test email for the success row.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| ページ初期表示（登録フォーム） | GET /entry | (none) | 200 OK; 全フィールド（氏名/カナ/住所/メール/パスワード/生年月日/性別/職業/規約同意）の入力欄が描画される。 | none. |
| 同意する／会員登録をする（送信ボタン）— 正常登録 | fill all required fields, check 規約同意, submit | name01/name02/kana01/kana02/companyName/postalCode/pref/addr01/addr02/phoneNumber, email, email_confirm(一致), password, password_confirm(一致), birth_year/month/day, sex, job, user_policy_check=1 | 303 SEE OTHER → Location: /entry/complete ; 会員登録完了ページに遷移。 | none — isBrowserFormSubmission() is true because email_confirm/password_confirm/user_policy_check keys are present (and/or birth parts) → browserForm path; success sets Code::SEE_OTHER + Location /entry/complete. param schema 'required' includes addr01 but the form always sends addr01="" so the key is present and required is satisfied. |
| 送信ボタン — メール/パスワード不一致 | submit with email_confirm or password_confirm not matching | email=a@x.com, email_confirm=b@x.com, password=p1, password_confirm=p2, user_policy_check=1, + required name fields | 400; form re-renders with inline "メールアドレスが一致しません。" / "パスワードが一致しません。" on the respective confirm fields; entered values repopulated. | none — formErrors() checks email/password confirm equality and returns rejectForm(400). Confirm the confirm-field error renders inline. |
| 送信ボタン — 規約未同意 | submit without checking 利用規約同意 | ...required fields..., user_policy_check=(空) | 400; inline error "利用規約に同意してください。" near the agreement checkbox. | none — guarded by formErrors when user_policy_check key present. NOTE: if the checkbox is unchecked browsers OMIT the key entirely; isBrowserFormSubmission still true via email_confirm/birth, and formErrors only checks user_policy when array_key_exists — an omitted key would SKIP the agreement check. Flag: verify an unchecked box still produces the error (template must submit a hidden 0 or the check must not rely on key presence). |
| 送信ボタン — 既登録メール | submit with an email that already exists | ...valid fields..., email=<already-registered>, user_policy_check=1 | 409; form re-renders with inline "このメールアドレスは既に登録されています。" on email. | none — EmailAlreadyRegisteredException caught → rejectForm(...,409). Verify 409 still renders the HTML form page. |

#### 会員登録確認 (Page/Entry/Confirm) — var/templates/Page/Entry/Confirm.html.twig

`/entry/confirm (rendered server-side after the entry form's confirm step)` ／ src/Resource/Page/Entry/Confirm.php (onGet) → final submit POSTs to src/Resource/Page/Entry.php (onPost)　<br>前提: anonymous; reached the confirm page carrying the entered registration values as hidden fields.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 会員登録をする（確認ページの送信ボタン） | click the final register button on the confirmation page | all 20 entry hidden fields (name01..user_policy_check) re-posted to /entry; mode=(button) value carried | 303 SEE OTHER → /entry/complete ; the account is actually created and 完了ページ shows. | ⚠️ Entry/Confirm only has onGet; the confirm screen re-POSTs the SAME fields to Page/Entry::onPost, which immediately CREATES the customer (no separate confirm-vs-commit distinction). So the confirm page is purely presentational; verify that pressing the button creates exactly one customer (no double-submit) and lands on /entry/complete. |

#### お問い合わせ (Page/Contact) — var/templates/Page/Contact.html.twig

`/contact` ／ src/Resource/Page/Contact.php (onGet/onPost)　<br>前提: anonymous.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 確認ページへ（送信ボタン name=mode value=confirm） | fill name/email/contents, click 確認ページへ | contactName01, contactName02, contactEmail, contactContents, mode=confirm, csrfToken | EC-CUBE faithful: render the CONFIRM page (Contact/confirm.twig) showing the entered values for review — NOT yet sent. (EC-CUBE: mode=confirm → render confirm, mode=complete → redirect /contact/complete.) | ⚠️ DEFECT (faithfulness gap): Contact::onPost treats ANY mode!=null as a final submit — mode=confirm runs the becoming chain and 303-redirects to /contact/complete, SKIPPING the confirmation step entirely. The user never sees a review page; the inquiry is sent on the first click. Flag. |
| 確認ページへ — 空入力 | click 確認ページへ with empty fields | contactName01=(空), contactName02=(空), contactEmail=(空), contactContents=(空), mode=confirm, csrfToken | 400; contact form re-renders with inline "入力してください。" on each empty field. | none — formErrors() catches empties → rejectForm(400) with form.error per field. This path is correct. |

#### お問い合わせ確認 (Page/Contact/Confirm) — var/templates/Page/Contact/Confirm.html.twig

`/contact (confirm view)` ／ src/Resource/Page/Contact/Confirm.php (onGet) → submit POSTs to src/Resource/Page/Contact.php (onPost)　<br>前提: anonymous; arrived at the confirm view with the entered inquiry as hidden fields.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 送信する（送信ボタン name=mode value=complete） | click 送信する on the confirm view | contactName01, contactName02, contactEmail, contactContents (hidden carriers), mode=complete, csrfToken | 303 SEE OTHER → Location: /contact/complete?ticketId=... ; お問い合わせ完了ページが表示される。 | none for the complete path — Contact::onPost with mode=complete submits and 303-redirects to /contact/complete. But because mode=confirm ALSO submits (see Contact screen defect), this confirm page is only reachable if some OTHER step renders it; verify the navigation actually lands here rather than being short-circuited. |
| 修正する（戻るボタン） | click the 修正する / 戻る button on the confirm view | the entered fields with mode set to a back/return value (template renders a second button name=mode) | 返回 the input form (Contact) with the previously entered values repopulated so the user can edit. | ⚠️ DEFECT: Contact::onPost has NO handling for a 'back'/'return' mode — any non-null mode that passes validation triggers a real submit + redirect to /contact/complete. So clicking 修正する would SEND the inquiry instead of returning to the editable form. Flag and verify the second button's value. |

#### パスワード再発行依頼 (Page/ForgotPassword) — var/templates/Page/ForgotPassword.html.twig

`/forgot-password` ／ src/Resource/Page/ForgotPassword.php (onGet/onPost)　<br>前提: anonymous.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| ページ初期表示 | GET /forgot-password | (none) | 200 OK; メールアドレス入力欄と「次へ」ボタンが描画される。 | none. |
| 次へ（送信ボタン）— 正常依頼 | enter a registered email and click 次へ | email=<registered>, csrfToken (NO mode field — the form has no mode hidden/button-name) | EC-CUBE faithful: redirect to /forgot/complete (forgot_complete) showing "パスワード再発行メールを送信しました" の完了ページ. | ⚠️ DEFECT: ForgotPassword::onPost returns Code::OK (NO redirect) with body {message:'リセット手続きのご案内をメールでお送りしました。'}, but the ForgotPassword TEMPLATE has NO region that renders `message` — it only renders the form. So in the html context the user sees no confirmation (same form or a body the template ignores). The user cannot tell the request succeeded. Also no /forgot/complete page is reached. Flag: missing observable success + missing redirect. |
| 次へ — 空メール | click 次へ with the email field empty | email=(空), csrfToken | EC-CUBE faithful: inline "入力してください。" on the email field, form re-rendered. | ⚠️ DEFECT: onPost signature is `onPost(string $email)` (REQUIRED, no null default) with NO browserForm/try-catch and NO field-error path. An empty email is passed straight to becoming(RequestPasswordResetInput) — a SemanticVariableException (EmailFormat) would be UNCAUGHT → framework error page, not an inline field error. (param schema email minLength:0 lets "" through transport, so the failure surfaces as an uncaught domain exception.) Flag. |
| 次へ — 未登録メール（列挙対策） | enter an email that is NOT registered and click 次へ | email=<unknown>@example.com, csrfToken | 同一の完了ページ/メッセージ（登録済みと見分けがつかない）— anti-enumeration: identical 200 outcome. | ⚠️ Same missing-observable defect as the success row: even the intended uniform message is never rendered by the template, so the anti-enumeration UX itself is invisible to the user. |

#### パスワード再設定 (Page/Reset) — var/templates/Page/Reset.html.twig

`/reset?resetKey=... (reached from the emailed reset link)` ／ src/Resource/Page/Reset.php (onGet/onPost)　<br>前提: anonymous; a valid, unexpired, unused resetKey issued for a customer.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| ページ初期表示 | GET /reset?resetKey=<valid> | resetKey (query) | 200 OK; 新しいパスワード・確認用パスワード（とemail）入力欄、hidden resetKey、登録するボタンが描画される。 | none for render — onGet returns 200 and carries resetKey into the hidden field. Note the template renders an `email` and `password_confirm` input that the POST handler ignores (see below). |
| 登録する（送信ボタン）— 正常リセット | enter a valid new password (twice) and click 登録する | resetKey=<valid>, email=<entered>, password=<new>, password_confirm=<new> | EC-CUBE faithful: addFlash('password_reset_complete') + redirect to /login (mypage_login) showing the "パスワードを再設定しました" flash; user can now log in with the new password. | ⚠️ DEFECT: Reset::onPost(string $resetKey, string $password) returns Code::OK with body {customerId:...} and NO redirect, NO success message. The Reset template renders NO success region (only form + an error `message` block that onPost never sets). So a successful reset shows no observable confirmation and stays on /reset — the user cannot tell it worked. No redirect to /login. Flag (this is exactly the 'success has no observable signal' anti-pattern). |
| 登録する — パスワード不一致 | enter password and a different password_confirm, click 登録する | resetKey=<valid>, email=<entered>, password=p1, password_confirm=p2 | field-level error "パスワードが一致しません。" on password_confirm, form re-rendered. | ⚠️ DEFECT: onPost signature has NO `password_confirm` param and performs NO match check — the mismatch is SILENTLY IGNORED and the password (p1) is reset anyway (or the domain proceeds). The confirm field is decorative. Flag: a real user typo in confirm is not caught. |
| 登録する — 無効/期限切れ resetKey | submit on a /reset page whose resetKey is wrong, expired, or already used | resetKey=<invalid>, email=<entered>, password=<new>, password_confirm=<new> | EC-CUBE faithful: error "再設定キーが見つかりません/無効です" を ec-errorMessage に表示してフォーム再描画（400相当, no enumeration of which failure). | ⚠️ DEFECT: ResetKeyInvalidException / SemanticVariableException are NOT caught in onPost (no try/catch, no browserForm path) — an invalid key throws UNCAUGHT → framework error page, not the template's inline `message`. The template comment claims onPost sets `message` on rejection, but the resource has no rejectForm. Flag: invalid-key submit produces an error page, not the faithful inline error. |

### storefront-frame


#### Block/search_product (header product-search block) — var/templates/Block/search_product.html.twig

`rendered inside every storefront frame (header); submits to /products` ／ src/Resource/Page/Products.php (onGet)　<br>前提: anonymous (block renders identically regardless of login state); product corpus loaded so the list has rows to show.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 商品を検索ボタン (虫眼鏡 / ec-headerSearch__keywordBtn, type=submit) — empty default search | クリックして検索フォームをそのまま送信する（キーワード未入力・カテゴリは唯一の選択肢「全ての商品」のまま）。これがトップpage検索が400で落ちた当の経路。 | GET /products?category_id=(空文字 — <option value="">全ての商品 が唯一の選択肢) & name=(空 — 未入力時はブラウザが name= を送る/送らない) | /products の商品一覧ページに遷移し、全公開商品が一覧描画される（絞り込みなしの全件）。EC-CUBE 既定のヘッダ全件検索と同じ。 | none — get-products.param.json の category_id は pattern ^[0-9]*$ / minLength:0 で空文字を許容、name も minLength:0。onGet は keyword 空なら productQuery->list(500,0) で全件を返し Code::OK で一覧描画。以前の 400 は解消済み。実機で 200 と商品行の存在を必ず確認すること。 |
| キーワード入力欄 (input[type=search][name=name], maxlength=50) + 検索ボタン — 実キーワード検索 | 存在する商品名の一部（例: コーパス内の実在名）を入力して送信する。 | GET /products?category_id=(空) & name=<入力したキーワード> | /products がキーワード一致の商品だけに絞り込まれて再描画される（一致件数が反映され、無関係な商品が消える）。 | none — onGet は keyword 非空で productQuery->search($keyword,500) を呼び一致分を返す。検索キーワードがテンプレートの検索結果見出し/件数に反映されるか（ユーザーが絞り込まれたと分かる observable があるか）を実機で確認。 |
| キーワード入力欄 — ヒット0件のキーワード検索 | どの商品にも一致しない文字列を入力して送信する。 | GET /products?category_id=(空) & name=<該当なしの文字列> | /products が「該当する商品が見つかりませんでした」相当の空結果表示で描画される（totalItemCount=0 のメッセージ領域）。 | ⚠️ FLAG: totalItemCount=0 のとき Product/list.twig が EC-CUBE 同等の0件メッセージを描画するか要確認。空配列を返すだけで0件メッセージが出ないと、ユーザーは検索が効いたのか壊れたのか区別できない。 |
| カテゴリ選択 select (select[name=category_id]) — 選択肢の欠落 | カテゴリで絞り込もうとプルダウンを開く。 | GET /products?category_id=(空のみ選択可) & name=... | EC-CUBE 既定ではカテゴリツリー（ジェラート/新入荷/…）が選択肢として並び、選ぶとそのカテゴリで絞り込めるべき。 | ⚠️ DEFECT/既知の簡略化: テンプレートは <option value="">全ての商品 の1択のみ（カテゴリツリー未移植、ブロックのコメントに明記）。Resource 側 onGet は category_id=1..6 でカテゴリ名フィルタを実装済みなのに、UI からカテゴリを選ぶ手段が無い。ユーザーはヘッダからカテゴリ絞り込みが一切できない=EC-CUBE 比の機能欠落。 |

#### Block/login (PC header nav login/logout block) — var/templates/Block/login.html.twig

`rendered inside the PC storefront frame header; logout form posts to /logout` ／ src/Resource/Page/Logout.php (onPost) — 他のリンク(マイページ/ログイン/新規会員登録/お気に入り)は単なる<a>ナビゲーション　<br>前提: ログアウトボタンを出すにはログイン済み顧客（is_logged_in()=true）。匿名状態では新規会員登録/お気に入り/ログインの<a>のみ表示されフォームは無い。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| ログアウトボタン (form.doLogout 内 button[type=submit]) — ログイン済み時のみ表示 | ログイン済み状態でヘッダのログアウトをクリックし POST 送信する。 | POST /logout, csrfToken=<csrfToken\|default(csrf_token()) の隠しフィールド値> | 303 でトップ / にリダイレクトされ、リロード後のヘッダが匿名状態（新規会員登録/ログイン）に切り替わる。EC-CUBE のログアウト後トップ遷移と同じ。 | none if CSRF token wired — Logout.onPost は Code::SEE_OTHER + Location:/ を返しセッションを clear。要確認: テンプレートの csrfToken が html context で実トークンに解決されているか（未解決だと #[CsrfToken] ガードで 403 になりログアウトできない）。実機でログイン→ログアウト→ヘッダが匿名表示に戻ることを確認。 |
| ログアウトボタン — 匿名状態（フォーム非表示の確認） | 未ログインでヘッダを開きログアウトフォームが描画されないことを確認する。 | (なし — is_logged_in()=false 分岐ではフォーム自体がレンダリングされない) | ログアウトボタンが存在せず、代わりに「ログイン」リンク(/login)が表示される。 | none — is_logged_in() ガードで分岐。誤って匿名にログアウトフォームが出ても Logout は idempotent(wasLoggedIn=false)で 303 / を返すだけなので破綻はしないが、UI 上は出ない想定。 |
| ナビゲーションリンク (マイページ/お気に入り/ログイン/新規会員登録 の <a>) | 各リンクをクリックして遷移する。 | (GET ナビゲーションのみ、フォーム送信なし) | ログイン済み: /mypage, /mypage/favorite-list へ遷移。匿名: /entry, /mypage/favorite-list, /login へ遷移し、それぞれのページが描画される。 | none for this block（リンクは静的）。ただし遷移先ページ自体の健全性は本グループ対象外（別グループで検証）。 |

#### Block/login_sp (SP header link-area login/logout block) — var/templates/Block/login_sp.html.twig

`rendered inside the SP storefront frame header; logout form posts to /logout` ／ src/Resource/Page/Logout.php (onPost) — 他は<a>ナビゲーション(カート/マイページ/新規会員登録/お気に入り/ログイン/ホーム)　<br>前提: ログアウトボタンを出すにはログイン済み顧客（is_logged_in()=true）。匿名では新規会員登録/ログインの<a>のみ。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| ログアウトボタン (form 内 button.ec-headerLink__item[type=submit]) — ログイン済み時のみ表示 | SPヘッダのログアウトをクリックし POST 送信する。 | POST /logout, csrfToken=<隠しフィールド値> | 303 でトップ / にリダイレクトされ、リロード後のSPヘッダが匿名状態（新規会員登録/ログイン）に切り替わる。 | none if CSRF token wired — login.html.twig と同一実体。csrfToken の html context 解決を要確認（未解決なら 403 でログアウト不可）。 |
| ナビゲーションリンク (カートを見る/マイページ/新規会員登録/お気に入り/ログイン/ホームに戻る の <a>) | 各リンクをクリックして遷移する。 | (GET ナビゲーションのみ) | /cart, /mypage(またはentry), /mypage/favorite-list, /login, / へそれぞれ遷移しページ描画。 | none for this block（静的リンク）。遷移先ページの健全性は別グループ。 |

### storefront-checkout


#### 注文情報入力（チェックアウト入口） — Page/Shopping.html.twig

`/shopping` ／ src/Resource/Page/Shopping.php　<br>前提: ログイン済み顧客。カートに1点以上の商品が入っており、有効な session prefix のカートが存在すること。匿名の場合は onGet が 303 /shopping/login へリダイレクトする。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 確認するボタン（type=submit, ec-blockBtn--action） | 支払方法ラジオを1つ選択し、配送/メッセージはデフォルトのまま「確認する」を押して /shopping/confirm へ POST する | redirect_to=(空), preOrderId={{preOrderId\|default('')}}（カートが解決されれば40桁hash、未解決なら空）, payment={選択した payment.paymentMethodId}, delivery=(マクロ/未実装), shipping_delivery_date=(マクロ/未実装), delivery_time=(マクロ/未実装), message=(マクロ/未実装) | EC-CUBE 忠実: 注文内容確認画面（/shopping/confirm）が表示され、選択した支払方法・配送先・カート明細・税込合計が再描画される。 | ⚠️ preOrderId が空で送られると Confirm 側 onPost(string $preOrderId) の param スキーマ/Semantic で弾かれ 400 または 404 になり確認画面に進めない。さらに payment ラジオに checked デフォルトが無い（template line 176）ため、未選択のまま送信すると payment フィールド自体が送られず Confirm.onPost のデフォルト 2 が黙って使われる—ユーザーが選んだつもりのない支払方法で確認画面に進む欠陥。 |
| 「変更」インラインボタン（配送/お支払の変更, type=button, line 139） | 配送先や支払方法の「変更」ボタンを押す | （type=button のため何も送信しない。JS の data-trigger/data-path に依存） | EC-CUBE 忠実: 配送先変更（/shopping/shipping）または該当変更画面へ遷移する。 | ⚠️ JS のみ: type=button でフォーム送信しない。no-JS 環境ではクリックしても無反応（遷移しない）。href リンクではないため確認が必要。 |
| 「お届け先を追加する」ボタン（type=button, data-path=/shopping/shipping-multiple, line 165） | 複数配送先設定へ進むためにボタンを押す | （type=button + data-path、JS依存。no-JS では送信なし） | /shopping/shipping-multiple（複数配送先設定画面）へ遷移する。 | ⚠️ JS-only: data-trigger=click/data-path に依存。JS無効だと無反応。 |
| 空カート時のページ表示 | カートが空（または session prefix のカート未解決）の状態で /shopping を開く | （GET のみ） | EC-CUBE 忠実: 「カートが空です」パネルが表示され、カートへ戻る導線（/cart）が出る。確認するボタンは出さない、または押せない。 | ⚠️ onGet は空カートでも Code::OK + canCheckout=false を返すだけ。template が canCheckout=false で確認フォームを隠さない場合、空 preOrderId のまま「確認する」が押せてしまい、その POST が Confirm 側で 400/404 になる（ユーザーは原因不明のエラー画面を見る）。canCheckout の分岐描画を実画面で要確認。 |

#### 注文内容のご確認 — Page/Shopping/Confirm.html.twig

`/shopping/confirm` ／ src/Resource/Page/Shopping/Confirm.php　<br>前提: ログイン済み顧客で、直前の /shopping から有効な preOrderId（処理中の pre-order）を保持していること。verify() が通る決済状態であること。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 注文するボタン（type=submit, ec-blockBtn--action, line 168） | 確認画面で「注文する」を押し /shopping/checkout へ POST する | csrfToken={{csrfToken\|default(csrf_token('shopping_checkout'))}}, preOrderId={{preOrderId}}（確認画面が描画した40桁hash） | EC-CUBE 忠実: 注文が確定し /shopping/complete?orderNo=<注文番号> へリダイレクトされ、注文完了画面が表示される（Checkout.onPost が SEE_OTHER → /shopping/complete）。 | none（Checkout.php onPost はブラウザ送信時 SEE_OTHER で /shopping/complete?orderNo= へ遷移する）。ただし csrfToken は html コンテキストで body が null のため template の csrf_token('shopping_checkout') フォールバックに依存している—CSRF トークンの整合性を実 POST で要確認（不整合なら 403）。 |
| 確認画面の onPost 経路（/shopping から payment 付きで到達） | Shopping ページの「確認する」から payment と preOrderId を POST して確認画面を生成する | preOrderId={{preOrderId}}, payment={選択した paymentMethodId（未選択ならデフォルト2）} | 確認画面が選択済み支払方法名・明細・合計付きで描画される。verify 失敗時は 303 /shopping/error へ遷移しエラー画面が出る。 | ⚠️ verify 失敗で OrderConfirmFailed のとき onPost は SEE_OTHER /shopping/error を返し body に message『決済の確認に失敗しました。』を入れるが、/shopping/error 画面がこの message を引き継いで表示する保証がない（リダイレクト先 body は別 GET）。エラー理由がユーザーに伝わるか実画面で要確認。 |

#### 購入ログイン — Page/Shopping/Login.html.twig

`/shopping/login` ／ src/Resource/Page/Shopping/Login.php　<br>前提: 匿名訪問者（未ログイン）。/shopping への匿名アクセスからリダイレクトで到達する。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| ログインボタン（type=submit, ボタン文言「ログイン」） | メールアドレスとパスワードを入力し /login へ POST する | _target_path=/shopping, _failure_path=/shopping/login, email={入力値}, password={入力値} | EC-CUBE 忠実: 認証成功で _target_path の /shopping（注文情報入力）へリダイレクトされチェックアウトを継続できる。認証失敗なら _failure_path の /shopping/login に戻りログインエラーメッセージが表示される。 | ⚠️ Login.php は onGet のみのピュアレンダラーで、POST 先は /login（Page/Login が処理）。このリソース自体は認証しない。実際の認証成立とリダイレクトは /login のセキュリティファイアウォール実装に依存するため、email/password を実送信して /shopping へ戻るか・失敗時に /shopping/login へ戻りエラー表示されるかを実画面で要確認（フォーム input が form.input マクロで正しい name=email/password を出しているかも含む）。 |
| ゲスト購入リンク（goShoppingNonMember）/ 会員登録リンク（goCustomerRegistration） | 「ゲスト購入」または「新規会員登録」リンクをクリックする | （GET リンク遷移） | それぞれ /shopping/non-member（ゲスト購入入力）/ /entry（会員登録）へ遷移する。 | none（リンク遷移）。リンクが template に出力されているかは要確認。 |

#### 非会員購入（ゲスト情報入力） — Page/Shopping/NonMember.html.twig

`/shopping/non-member` ／ src/Resource/Page/Shopping/NonMember.php　<br>前提: 匿名訪問者。カートに商品があること（session prefix のカートが解決されると preOrderId が採番される）。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 次へボタン（type=submit, 文言「次へ」）— 正常系 | 全必須フィールド（氏名/カナ/郵便/都道府県/住所/電話/メール+確認）を有効値で入力し /shopping/non-member へ POST する | name01={入力}, name02={入力}, kana01={入力}, kana02={入力}, companyName={任意}, postalCode={入力}, pref={都道府県ID}, addr01={入力}, addr02={入力}, phoneNumber={入力}, email={入力}, email_confirm={入力} | EC-CUBE 忠実: ブラウザ送信（email_confirm 付き）では 303 で /shopping/confirm?preOrderId=<id>&paymentMethodId=<id> へリダイレクトされ、注文確認画面が表示される。 | ⚠️ リダイレクト判定 isBrowserFormSubmission() は email_confirm が $this->uri->query に存在するかで分岐する。実ブラウザ POST のボディ値が query に乗る前提—POST ボディが query に入らない経路だと CREATED(201) + /shopping?preOrderId= を返し、ブラウザは 201 をナビゲートできず確認画面に進めない。実送信で 303 になるか要確認。 |
| 次へボタン — バリデーション異常系（必須未入力 / メール不一致） | 必須フィールドを空、または email と email_confirm を不一致で送信する | name01=(空) ほか必須欄空、または email=a@b.com, email_confirm=c@d.com | EC-CUBE 忠実: 400 でフォームが再描画され、当該フィールド直下に『入力してください。』『メールアドレスが一致しません。』等のフィールド単位エラーが表示され、入力済みの値は保持される。 | ⚠️ rejectForm() は form->setDomainError(field,msg) で各フィールドにエラーを載せ、fillValues で入力値を復元する。template の {{ form.input(...) }} がフィールド単位エラー表示と値復元を実際に描画するか要確認—描画しないと body.message のトップエラーしか見えず、どの欄が悪いか分からない。 |

#### お届け先選択 — Page/Shopping/Shipping.html.twig

`/shopping/shipping` ／ src/Resource/Page/Shopping/Shipping.php　<br>前提: ログイン済み顧客。登録済み配送先（アドレス帳）が1件以上あること。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 選択したお届け先に送るボタン（type=submit, line 80） | 配送先ラジオを1つ選び /shopping/shipping へ POST する | address={address.id}（選択したアドレス帳行のID） | EC-CUBE 忠実: 選択した配送先が pre-order に反映され /shopping（注文情報入力）へ戻り、配送先欄が選んだ住所で再描画される。 | ⚠️ 重大: onGet の addresses が常に空（Wave-future TODO, body['addresses']=[]）。template の {% for address in addresses %} が0件ループになりラジオが1つも描画されない—ユーザーは配送先を選べず、空のフォームを送るしかない。さらに onPost は配送先を永続化せず SEE_OTHER /shopping を返すだけで body の message『お届け先を選択しました。』はリダイレクト先 /shopping に引き継がれない=成功表示が出ない。実質スタブ。 |

#### お届け先変更（住所入力） — Page/Shopping/ShippingEdit.html.twig

`/shopping/shipping-edit` ／ src/Resource/Page/Shopping/ShippingEdit.php　<br>前提: ログイン済み顧客。チェックアウト中（配送先選択画面の「変更」から到達）。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録するボタン（type=submit, 文言「登録する」） | 住所フォーム10項目を入力し /shopping/shipping-edit へ POST する | name01={入力}, name02={入力}, kana01={入力}, kana02={入力}, companyName={任意}, postalCode={入力}, pref={都道府県ID}, addr01={入力}, addr02={入力}, phoneNumber={入力} | EC-CUBE 忠実: 入力した配送先が pre-order に登録され /shopping へ戻り、配送先欄が更新後の住所で再描画される（または成功メッセージ『お届け先を更新しました。』が表示される）。 | ⚠️ 重大: onPost は永続化を一切行わず（pure stub）、SEE_OTHER /shopping を返し body に message を入れるだけ。リダイレクト先 /shopping は body.message を引き継がないため成功表示が出ず、住所も保存されない=次の画面に反映されない。ユーザーは登録できたか分からない（観測可能シグナルなし=欠陥）。さらに onPost の param デフォルトは name01='' 等で、空送信でも 303 になりバリデーションエラーが一切出ない。 |

#### 複数配送先設定 — Page/Shopping/ShippingMultiple.html.twig

`/shopping/shipping-multiple` ／ src/Resource/Page/Shopping/ShippingMultiple.php　<br>前提: ログイン済み顧客。カートに複数商品があり、配送先が複数登録されていること。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 選択したお届け先に送るボタン（type=submit, id=button__confirm, line 123） | 各カート商品に配送先を割り当て /shopping/shipping-multiple へ POST する | （フォームに実フィールドが無い: screen_forms.json の fields=[]。template の {% for orderItem in cartItems %} 由来の割当 select が cartItems 空で0件描画） | EC-CUBE 忠実: 商品ごとの配送先割当が pre-order に保存され /shopping（または確認）へ戻り、複数配送の内訳が反映される。 | ⚠️ 重大: onGet の cartItems / addresses が常に空（body=[]）。template の {% for orderItem in cartItems %} が0件で、割当 select も配送先候補も描画されない—ユーザーは何も割り当てられず、フィールドの無い空フォームを送るだけ。onPost(array $allocations=[]) は allocationCount=0 を SEE_OTHER /shopping で返すだけで永続化せず、message も引き継がれない。実質スタブで割当機能が成立していない。 |
| 新規お届け先を追加する リンク（line 107, href=/shopping/shipping-multiple-edit） | 「新規お届け先を追加する」をクリックする | （GET リンク遷移） | /shopping/shipping-multiple-edit（複数配送の新規住所追加フォーム）へ遷移する。 | none（リンク遷移は機能する）。ただし遷移先で住所を追加しても永続化されないため、戻ってきても割当候補に増えない（下記 ShippingMultipleEdit 参照）。 |

#### 複数配送の新規お届け先追加 — Page/Shopping/ShippingMultipleEdit.html.twig

`/shopping/shipping-multiple-edit` ／ src/Resource/Page/Shopping/ShippingMultipleEdit.php　<br>前提: ログイン済み顧客。複数配送先設定画面から「新規お届け先を追加する」で到達。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録するボタン（type=submit, 文言「登録する」） | 住所フォーム10項目を入力し /shopping/shipping-multiple-edit へ POST する | name01={入力}, name02={入力}, kana01={入力}, kana02={入力}, companyName={任意}, postalCode={入力}, pref={都道府県ID}, addr01={入力}, addr02={入力}, phoneNumber={入力} | EC-CUBE 忠実: 入力住所が pre-order の配送先集合に追加され /shopping/shipping-multiple へ戻り、追加した住所が割当候補（addresses リスト）に新規行として現れる。 | ⚠️ 重大: onPost は永続化せず（pure stub）SEE_OTHER /shopping/shipping-multiple を返し body に message『複数配送先のお届け先を更新しました。』を入れるだけ。リダイレクト先の ShippingMultiple は addresses を常に空で返すため、追加したはずの住所が候補に現れない=ユーザーから見て何も起きていない（観測可能な成功シグナルなし=欠陥）。onPost param デフォルト name01='' 等で空送信もエラーにならず 303 になる。 |

### storefront-mypage


#### お届け先情報編集 / Page/Mypage/Address (var/templates/Page/Mypage/Address.html.twig, src/Resource/Page/Mypage/Address.php)

`/mypage/address?addressId=<id>  (edit) | /mypage/address (new)` ／ src/Resource/Page/Mypage/Address.php　<br>前提: Logged-in customer. For the EDIT case the customer must own at least one address row (addressId belonging to the session customer). For the NEW case no addressId.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録する ボタン (EDIT screen: /mypage/address?addressId=<own id>) | Open the edit form (it is pre-filled from the stored address by onGet), optionally change a field (e.g. addr02), and submit. | csrfToken=<token>, name01=<prefilled e.g. 山田>, name02=<prefilled e.g. 太郎>, kana01=<prefilled>, kana02=<prefilled>, companyName=<prefilled or 空>, postalCode=<prefilled e.g. 1500001>, pref=<prefilled pref id e.g. 13>, addr01=<prefilled>, addr02=<edited>, phoneNumber=<prefilled>. Form action is /mypage/address?addressId=<id>&_method=put so the browser POST is rewritten to PUT. | 303 redirect to /mypage/address-list; the address list re-renders showing the edited row (faithful EC-CUBE: DeliveryController redirects to mypage_delivery). HtmlMutationResponse sets Code::SEE_OTHER + Location /mypage/address-list, so this is plausibly correct. | none (onPut calls redirectToAddressListOnHtmlSuccess -> HtmlMutationResponse -> 303 to /mypage/address-list). RISK to watch: the success is only visible as the list re-rendering the changed value; if the edit did not actually change a visible field the user sees no diff. Also depends on the _method=put override being honored by the HTML router — if it is not, the POST falls through to a non-existent POST handler on /mypage/address and the edit silently does nothing. |
| 登録する ボタン (EDIT screen) with an invalid field | Submit the edit form with a malformed value, e.g. clear postalCode or set phoneNumber to letters. | csrfToken=<token>, postalCode=(空), phoneNumber=abc, plus the other prefilled fields (name01…addr02). action=/mypage/address?addressId=<id>&_method=put. | Field-level validation error rendered inline via {{ form.error('postalCode') }} / {{ form.error('phoneNumber') }} on the same form (the Be Becoming chain raises SemanticVariableException -> 400, mapped to field-named errors). | ⚠️ Verify the 400 path actually re-renders the Address form with form.error(...) populated rather than a bare 400 page. The resource maps SemanticVariableException->400 but the template only shows the error if the html error projection re-feeds the AddressForm with field errors; if the 400 short-circuits to a generic error page the user gets no inline field error. |
| 登録する ボタン (NEW screen: /mypage/address, no addressId) | Open the empty add-address form and submit a new address. | csrfToken=<token>, name01=<空 default>, name02=<空>, kana01=<空>, kana02=<空>, companyName=<空>, postalCode=<空>, pref=<空 select option value=''>, addr01=<空>, addr02=<空>, phoneNumber=<空>. Form action is /mypage/address-list (POST, NOT this resource). | On valid input: 303 redirect to /mypage/address-list with the new row appearing. On the empty defaults shown here: field-level validation errors for the required fields (name01/kana01/postalCode/pref/addr01/phoneNumber). EC-CUBE faithful: redirect to mypage_delivery on success. | ⚠️ OUT OF MY RESOURCE — the NEW form POSTs to /mypage/address-list (address-list collection resource), not src/Resource/Page/Mypage/Address.php. FLAG: the empty pref <select option value=''> and the empty required fields are exactly the empty-default trap — confirm the address-list onPost surfaces these as inline field errors and does not 400/500 on the empty pref. The Address resource onGet only renders the form; it has no POST handler, so a stray POST to /mypage/address (instead of /mypage/address-list) would 405/404. |
| 削除する (DELETE address) — affordance, NOT present on this template | Delete an owned address. (The Address resource exposes onDelete, but Address.html.twig renders NO delete button — delete is triggered from the address-list screen.) | csrfToken=<token>, addressId=<own id>, _method=delete (when wired from the list). | Row disappears from /mypage/address-list; 303 redirect to /mypage/address-list (onDelete -> redirectToAddressListOnHtmlSuccess). | ⚠️ FLAG: onDelete exists on this resource but no UI element on Address.html.twig invokes it — the delete affordance lives on the address-list template (outside this group). Confirm the list screen actually renders a delete control; otherwise onDelete is dead from the UI. |

#### 会員情報編集 / Page/Mypage/Change (var/templates/Page/Mypage/Change.html.twig, src/Resource/Page/Mypage/Change.php)

`/mypage/change` ／ src/Resource/Page/Mypage/Change.php　<br>前提: Logged-in customer. onGet pre-populates the form from the session customer's current profile (no session -> 401).


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録する ボタン (会員情報編集フォーム送信) | Open /mypage/change (form pre-filled by onGet with current profile), edit a field (e.g. addr02 or phoneNumber), and submit. | csrfToken=<token>, name01=<prefilled>, name02=<prefilled>, kana01=<prefilled>, kana02=<prefilled>, companyName=<prefilled or 空>, postalCode=<prefilled>, pref=<prefilled id>, addr01=<prefilled>, addr02=<edited>, phoneNumber=<prefilled>, email=<prefilled>, email_confirm=<空 — onGet does not pre-fill email_confirm>, password=<空>, password_confirm=<空>, birth_year=<空 select>, birth_month=<空>, birth_day=<空>, sex=<空 radio>, job=<空 select>. Form action=/mypage/change (POST). | EC-CUBE FAITHFUL: 303 redirect to /mypage/change-complete (mypage_change_complete) which renders the '会員情報を変更しました' completion screen. That completion page is the user-observable success signal. | ⚠️ DEFECT — Change::onPost sets only Code::OK and a tiny body (customerId/email/name01/name02); it does NOT call MutationResponse, sets NO Location, and does NOT redirect to /mypage/change-complete (a ChangeComplete resource exists but is never reached). In the HTML context the user is left on the same /mypage/change form (or a re-render) with NO success message and NO redirect — they cannot tell the save worked. This is exactly the 'success has no observable signal' defect. Additionally onPost takes only a subset of fields (email,name01,name02,kana,company,phone,postalCode,pref,addr01,addr02); it IGNORES email_confirm/password/password_confirm/birth_*/sex/job that the form submits — confirm whether password change / birthday / sex / job edits are silently dropped vs EC-CUBE which persists them. |
| 登録する ボタン with email change colliding | Change email to one already registered to another customer and submit. | csrfToken=<token>, email=<existing-other-customer@example.com>, email_confirm=<same>, plus prefilled name/address fields. | Field-level validation error on the email field ('すでに登録されているメールアドレスです' / 'mail address is already used') rendered via {{ form.error('email') }}, staying on the form. | ⚠️ Resource maps EmailAlreadyRegisteredException->409, but Change::onPost has NO catch/render wiring shown for re-rendering the ChangeForm with the email error in HTML — verify the 409 surfaces as an inline form.error('email') and not a bare 409 page. Also email_confirm is submitted but onPost signature ignores it, so the email/email_confirm match check may never run. |
| 登録する ボタン with invalid field format | Submit with a malformed required field, e.g. clear name01 or set postalCode to letters. | csrfToken=<token>, name01=(空), postalCode=abc, plus other prefilled fields. | Inline field-level validation error via {{ form.error('name01') }} / {{ form.error('postalCode') }} on the same form (SemanticVariableException->400). | ⚠️ Confirm the 400 re-renders the ChangeForm with field errors rather than a generic 400 page; the template renders form.error(...) per field but only works if the error projection re-feeds the form. |

#### ご注文履歴詳細 / Page/Mypage/History (var/templates/Page/Mypage/History.html.twig, src/Resource/Page/Mypage/History.php)

`/mypage/history?orderNo=<orderNo>` ／ src/Resource/Page/Mypage/History.php　<br>前提: Logged-in customer who OWNS the order identified by orderNo. (AUTHZ in the Be layer: another customer's orderNo -> 403; unknown orderNo -> 404; no session -> 401.)


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 再注文する ボタン | On the order-history detail page, click 再注文する to re-add the order's items to the cart. | csrfToken=<token, csrf_token('mypage_reorder')>, orderNo=<the {{ orderNo }} hidden value, e.g. the order shown>. Form action=/mypage/reorder (POST -> Page/Mypage/Reorder resource). | 303 redirect to /cart with the reordered items present in the cart; the header cart badge increments accordingly (EC-CUBE faithful: reorder action redirects to cart). Reorder::onPost calls MutationResponse(Code::CREATED, '/cart') -> HtmlMutationResponse rewrites to 303 SEE_OTHER + Location /cart. | none for the redirect itself (Reorder resource exists and 303s to /cart). RISK to watch: (1) target resource is Page/Mypage/Reorder, outside this group — confirm it actually adds the line items, not just redirects; (2) the cart badge: this project previously hardcoded the header cart badge to 0, so even after a successful reorder the badge may still show 0 — verify the badge reflects the reordered quantity, not a hardcoded 0. |
| 戻る リンク (anchor, not a form) | Click 戻る. | GET /mypage (plain link, no form fields). | Navigates to /mypage (注文履歴一覧 / mypage top). | none (static link). Included for completeness — the only other interactive element on the screen. |

#### 退会手続き(確認前警告) / Page/Mypage/Withdraw (var/templates/Page/Mypage/Withdraw.html.twig, src/Resource/Page/Mypage/Withdraw.php)

`/mypage/withdraw` ／ src/Resource/Page/Mypage/Withdraw.php　<br>前提: Logged-in customer (onGet returns 401 with 'この操作を行うにはログインが必要です。' if no session, or if the session points to a withdrawn/stale customerId).


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 退会手続きへ ボタン (name=mode value=confirm) | On the warning page click 退会手続きへ. In EC-CUBE this only ADVANCES to the final confirmation screen — it must NOT yet delete the account. | csrfToken=<token>, mode=confirm (the submit button is name='mode' value='confirm'). Form action=/mypage/withdraw (POST). | EC-CUBE FAITHFUL: render the final-confirmation screen Mypage/withdraw_confirm.twig ('退会手続きを実行してもよろしいでしょうか？') with a cancel link + 'はい、退会します' execute button. The account is NOT yet withdrawn at this step. | ⚠️ DEFECT / divergence — Withdraw::onPost IGNORES the mode field entirely and performs the withdrawal immediately (runs the WithdrawCustomerInput Becoming chain, clears the cart/replaces email/sends mail) then 303-redirects to /mypage/withdraw-complete. So clicking '退会手続きへ' on the FIRST screen deletes the account, skipping EC-CUBE's mode=confirm intermediate confirmation. The two-step guard EC-CUBE provides (warning -> final confirm -> complete) is collapsed; a single accidental click is destructive. Observable mismatch: user expects the '退会しますか？' confirm page, gets the completion/redirect instead. |
| no-JS / accessibility of the destructive submit | Confirm whether there is any second confirmation between this button and account deletion. | csrfToken=<token>, mode=confirm. | There SHOULD be an intermediate '退会しますか？' confirmation (WithdrawConfirm screen) before the irreversible delete. | ⚠️ Because onPost ignores mode, there is NO server-side gate between this button and the irreversible withdrawal. FLAG as a real safety defect: account deletion happens on the first POST. |

#### 退会手続き(最終確認) / Page/Mypage/WithdrawConfirm (var/templates/Page/Mypage/WithdrawConfirm.html.twig, src/Resource/Page/Mypage/WithdrawConfirm.php)

`/mypage/withdraw-confirm` ／ src/Resource/Page/Mypage/WithdrawConfirm.php　<br>前提: Logged-in customer. NOTE: in EC-CUBE this screen is reached by POSTing mode=confirm from the warning page; in BeMart it is a standalone thin GET renderer at /mypage/withdraw-confirm (the ALPS surface collapsed the two-step flow). WithdrawConfirm::onGet is a pure renderer with NO session/customer context, so it does not enforce login itself.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| はい、退会します ボタン (name=mode value=complete) | On the final-confirmation page click 'はい、退会します' to execute the withdrawal. | csrfToken=<token>, mode=complete (submit button name='mode' value='complete'). Form action=/mypage/withdraw (POST -> Withdraw::onPost). | EC-CUBE FAITHFUL: account withdrawn, then 303 redirect to /mypage/withdraw-complete ('退会手続きが完了しました'). Withdraw::onPost runs the Becoming chain and MutationResponse(Code::OK,'/mypage/withdraw-complete') -> HtmlMutationResponse 303 SEE_OTHER. Session is cleared by the EventListener afterward. | ⚠️ The redirect path itself is plausibly correct (303 -> /mypage/withdraw-complete). BUT mode=complete is ignored by onPost (same handler as mode=confirm), so this button is functionally identical to the warning-page button — both delete immediately. Confirm /mypage/withdraw-complete actually renders the completion message and that the session is invalidated (subsequent /mypage returns to login). |
| いいえ、退会しません リンク (cancel anchor) | Click 'いいえ、退会しません'. | GET /mypage (plain link, no form fields). | Navigates back to /mypage with the account intact (no withdrawal performed). | none (static cancel link). |
| Mypage navi welcome line (page chrome, not interactive) | Load /mypage/withdraw-confirm and observe the navi welcome greeting. | GET only. | Welcome line should show the logged-in customer's name (name01 name02). | ⚠️ KNOWN missing body field — WithdrawConfirm::onGet is a thin renderer with no session-bound customer, so name01/name02 are absent and the navi welcome renders an empty name (cosmetic divergence from EC-CUBE which shows the customer name). |

### admin-product


#### カテゴリ登録/編集 (Page/Admin/Category/Edit) — var/templates/Page/Admin/Category/Edit.html.twig

`/admin/category/edit  (and /admin/category/edit?categoryId=<id>)` ／ src/Resource/Page/Admin/Category/Edit.php (onGet only; the form POSTs to the sibling CategoryList resource)　<br>前提: authenticated admin. For pre-fill variant, at least one category must exist (e.g. categoryId=1).


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (category_form の submit) | ツリー左の add/edit フォームにカテゴリ名を入れて『登録』を押す (新規 categoryId 空) | POST /admin/category/category-list — csrfToken=..., categoryId=(空), name=<入力したカテゴリ名>, parent_id=(空), sort_no=<入力 or 空> | 303 redirect to /admin/category/category?categoryId=<新ID>、その後カテゴリ詳細/一覧で新カテゴリが見える (EC-CUBE: 『カテゴリを登録しました』フラッシュ + 一覧に追加) | ⚠️ 高確率で失敗。このテンプレートの form は AdminCategoryForm マクロで name='name'/'parent_id'/'sort_no' (snake_case) を送るが、CategoryList::onPost は categoryName(必須) と sortNo(必須 int) を要求する。実フォームに categoryName/sortNo が一切無いため必須パラメータ欠落で 400。兄弟テンプレート CategoryList.html.twig は正しく categoryName/sortNo を送るのに、この Edit ページだけフィールド名が契約とズレている典型のトラップ。 |

#### カテゴリ登録一覧 (Page/Admin/Category/CategoryList) — var/templates/Page/Admin/Category/CategoryList.html.twig

`/admin/category/category-list` ／ src/Resource/Page/Admin/Category/CategoryList.php (onGet 一覧, onPost 作成)　<br>前提: authenticated admin。CSRF: csrf_token('admin_category')。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (カテゴリ登録カードの submit) | カテゴリ名を入力し並び順(既定1)のまま『登録』を押す | POST /admin/category/category-list — csrfToken=..., categoryName=<入力>, sortNo=1 | 303 redirect to /admin/category/category?categoryId=<新ID> (HtmlMutationResponse は CREATED を 303 に変換)。リダイレクト先 or 戻った一覧に新カテゴリ行が表示される。EC-CUBE 相当の『カテゴリを登録しました』成功表示が出ること。 | ⚠️ リダイレクト先 /admin/category/category?categoryId=<id> が Category リソースの GET だが、これは JSON ボディを返す単票エンドポイントで HTML カテゴリ一覧ではない。リダイレクト後にユーザーが見る画面が EC-CUBE のカテゴリツリーに戻らない恐れ。成功フラッシュの表示元も未確認 — 成功メッセージが描画されないと『登録できたか分からない』状態になる。要 実機確認。 |
| 削除リンク (一覧行の『削除』 <a data-method=delete>) | カテゴリ行の『削除』を押す | GET/anchor href=/admin/category/category?categoryId=<id>&_method=delete, data-method=delete, token-for-anchor=csrf_token_for_anchor('admin_category_delete') | 対象カテゴリ行が一覧から消える / 303 redirect to /admin/category/category-list (Category::onDelete は Location=/admin/category/category-list) | ⚠️ data-method='delete' の <a> は EC-CUBE グローバル JS (eccube.js) が _method=delete の POST に変換する前提。その JS 配線が BeMart に無ければ no-JS クリックは単なる GET ナビゲーションになり削除されない。JS 依存アフォーダンスのため要確認。 |

#### カテゴリ単票 (Page/Admin/Category/Category) — テンプレ: カテゴリ編集インライン form (action /admin/category/category?categoryId=...&_method=put)

`/admin/category/category?categoryId=<id>` ／ src/Resource/Page/Admin/Category/Category.php (onGet/onPut/onDelete)　<br>前提: authenticated admin。対象 categoryId が存在すること。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 保存ボタン (categoryId 隠しフィールド付き編集 form の submit) | カテゴリ名・並び順を編集して『保存』を押す | POST /admin/category/category?categoryId=<id> — _method=put, categoryId=<id>, categoryName=<編集値>, sortNo=<編集値> | 303 redirect to /admin/category/category?categoryId=<id> (onPut: Code::OK→HTML では SEE_OTHER, Location 同 categoryId)。編集後の categoryName/sortNo が反映表示される。EC-CUBE: 『カテゴリを編集しました』。 | ⚠️ onPut の Location は同一 categoryId への自己リダイレクトで JSON 単票に戻る。成功フラッシュの描画有無が未確認。値が反映されていれば成功と判定可。サーバ側のフィールド名(categoryName/sortNo)はこの form と一致しており 400 リスクは低い。 |

#### カテゴリCSV (Page/Admin/Category/Csv) — var/templates/Page/Admin/Category/Csv.html.twig

`/admin/category/csv` ／ src/Resource/Page/Admin/Category/Csv.php (onGet エクスポート, onPost インポート=Phase2 スタブ)　<br>前提: authenticated admin。CSRF 必須。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| CSV登録ボタン (csv textarea + submit) | CSV 本文を textarea に貼って『CSV登録』を押す | POST /admin/category/csv — csv=<textarea本文>, csrfToken=... | 303 redirect to /admin/category/category-list。EC-CUBE: 『CSVを取り込みました』。本来は一覧にインポート行が反映されるべき。 | ⚠️ onPost は accepted=false の Phase2 スタブで実際には永続化しない。リダイレクトは出るが一覧に何も増えない → ユーザーは『登録した』と思うのに観測可能な変化が無い。スタブ自体が欠陥 (body.accepted=false / message は出るが HTML 画面で表示されるか要確認)。FLAG: 成功シグナルが実データに現れない。 |

#### 商品CSV登録アップロード (Page/Admin/Product/CsvCategory) — var/templates/Page/Admin/Product/CsvCategory.html.twig

`/admin/product/csv-category` ／ src/Resource/Page/Admin/Product/CsvCategory.php (onGet のみ — AbstractCsvUpload)　<br>前提: authenticated admin (adminId 無なら 403)。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| CSVファイルをアップロードボタン (import_file ファイル選択 + submit) | CSV ファイルを選択して『CSVファイルをアップロード』を押す | POST (multipart) /admin/category/csv — import_file=<選択ファイル> (フォーム action は csv_category.twig 由来で /admin/category/csv?csv= に解決) | 303 redirect to /admin/category/category-list、CSV インポート完了表示。EC-CUBE: アップロード→『CSVを取り込みました』。 | ⚠️ 失敗。この画面は GET 専用シェル (CsvCategory に onPost 無し)。フォームはファイルアップロード(import_file, multipart)を /admin/category/csv へ送るが、Category\Csv::onPost は csv (テキスト本文) パラメータを要求し import_file を受けない上に Phase2 スタブで永続化しない。フィールド名不一致 (import_file vs csv) + multipart 未対応 → 400/未処理。実機で no-op になる典型。 |

#### 規格分類CSV登録アップロード (Page/Admin/Product/CsvClassCategory) — var/templates/Page/Admin/Product/CsvClassCategory.html.twig

`/admin/product/csv-class-category` ／ src/Resource/Page/Admin/Product/CsvClassCategory.php (onGet シェル, onPost 取込)　<br>前提: authenticated admin。CSRF 必須。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| CSVファイルをアップロードボタン (import_file ファイル選択 + submit) | CSV ファイルを選んで『CSVファイルをアップロード』を押す | POST (multipart) /admin/product/csv-class-category — import_file=<ファイル> | 303 redirect to /admin/class-category/class-category-list、『規格分類CSVを取り込みました。』表示。一覧に取込分類が反映。 | ⚠️ フィールド名不一致。テンプレは import_file(ファイル) を送るが onPost は csv='' (テキスト) を要求。multipart ファイル名 import_file が csv にバインドされず、空 csv で onPost が走り accepted の実体が無いまま 303 する恐れ。ファイル内容が取り込まれない。要確認 (no-JS multipart→csv 変換の有無)。 |

#### 規格CSV登録アップロード (Page/Admin/Product/CsvClassName) — var/templates/Page/Admin/Product/CsvClassName.html.twig

`/admin/product/csv-class-name` ／ src/Resource/Page/Admin/Product/CsvClassName.php (onGet シェル, onPost 取込)　<br>前提: authenticated admin。CSRF 必須。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| CSVファイルをアップロードボタン (import_file + submit) | CSV ファイルを選んで『CSVファイルをアップロード』を押す | POST (multipart) /admin/product/csv-class-name — import_file=<ファイル> | 303 redirect to /admin/class-name/class-name-list、『規格名CSVを取り込みました。』表示。一覧に反映。 | ⚠️ CsvCategory/CsvClassCategory と同様: import_file(multipart) vs onPost(csv='' テキスト) の不一致。ファイル内容が csv にバインドされず空取込で 303 になる恐れ。実機での取込実体を確認。 |

#### 商品CSV登録アップロード (Page/Admin/Product/CsvProduct) — var/templates/Page/Admin/Product/CsvProduct.html.twig

`/admin/product/csv-product` ／ src/Resource/Page/Admin/Product/CsvProduct.php (onGet のみ)　<br>前提: authenticated admin (adminId 無なら 403)。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| CSVファイルをアップロードボタン (import_file + submit) | CSV ファイルを選んで『CSVファイルをアップロード』を押す | POST (multipart) /admin/product-csv — import_file=<ファイル> | 303 redirect to /admin/product-list、取込件数表示。EC-CUBE: 『商品CSVを取り込みました(n件)』。一覧に新商品が並ぶ。 | ⚠️ フィールド名/形式不一致。ここは GET 専用シェル。フォームは import_file(multipart) を /admin/product-csv に送るが ProductCsv::onPost は csv (テキスト本文, ヘッダ行付き) を要求。import_file という multipart ファイルは csv にバインドされず → 'CSVヘッダーがありません。' 等の BAD_REQUEST か未処理。実機で取込されない可能性大。 |

#### 商品CSV(テキスト) (Page/Admin/ProductCsv) — var/templates/Page/Admin/ProductCsv.html.twig

`/admin/product-csv` ／ src/Resource/Page/Admin/ProductCsv.php (onGet エクスポート, onPost テキストCSV取込)　<br>前提: authenticated admin。CSRF 必須。textarea にヘッダ行 productCode,productName,price02,... を含む CSV。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| CSV登録ボタン (csv textarea + submit) | ヘッダ行と少なくとも1データ行を含む CSV を貼って『CSV登録』を押す | POST /admin/product-csv — csv=<textarea本文 (1行目に productCode,productName,price02 等の見出し)>, csrfToken=... | 303 redirect to /admin/product-list、取込件数(count)分の商品が一覧に出現。EC-CUBE: 『商品CSVを取り込みました』。 | ⚠️ 成功パスは実装あり(各行を doCreateProduct チェーンに流す)。ただしヘッダに productCode/productName/price02 が無いと BAD_REQUEST('商品CSWの必須列がありません。') を返す — これは画面に表示されるか要確認。成功時のリダイレクトは出るが、取込件数/成功フラッシュが HTML に描画されるか未確認 (body.count は JSON 用)。 |

#### 規格分類CSVダウンロード (Page/Admin/ClassCategory/ClassCategoryExport) — var/templates/Page/Admin/ClassCategory/ClassCategoryExport.html.twig

`/admin/class-category/class-category-export (?classNameId=<id>)` ／ src/Resource/Page/Admin/ClassCategory/ClassCategoryExport.php (onGet ダウンロード)　<br>前提: authenticated admin。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 規格分類CSV登録ボタン (csv textarea + submit) — このページに同居するインポート form | CSV 本文を貼って『規格分類CSV登録』を押す | POST /admin/product/csv-class-category — csv=<textarea本文> | 303 redirect to /admin/class-category/class-category-list、『規格分類CSVを取り込みました。』。一覧に反映。 | ⚠️ このページ自体は GET エクスポート専用 (onGet が CSV を Content-Disposition で返す)。同居する import form は別リソース CsvClassCategory::onPost(csv) に csv テキストを正しく送るのでフィールド名は一致。ただし CsvClassCategory の取込実体(永続化)が ClassCsvCompatibility サービス依存で、実際にデータが増えるか要確認。ダウンロード自体(GET)はファイルが落ちれば成功。 |

#### 規格分類一覧 (Page/Admin/ClassCategory/ClassCategoryList) — var/templates/Page/Admin/ClassCategory/ClassCategoryList.html.twig

`/admin/class-category/class-category-list?classNameId=<id>` ／ src/Resource/Page/Admin/ClassCategory/ClassCategoryList.php (onGet 一覧, onPost 作成)　<br>前提: authenticated admin。classNameId が必要 — 規格名(ClassName)を先に1件作っておく。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 新規作成ボタン (doCreateClassCategory form の submit) | 分類名を入力して『新規作成』を押す | POST /admin/class-category/class-category-list?classNameId=<id> — csrfToken=..., classCategoryName=<入力> | 303 redirect to /admin/class-category/class-category-list?classNameId=<id>、一覧に新分類値が追加表示。EC-CUBE: 『規格分類を追加しました』。 | ⚠️ onPost は classNameId(必須 string) を要求し、これは form action のクエリ ?classNameId={{ classNameId }} 由来。グリッド未スコープ表示(classNameId 空)から開いた場合 classNameId='' になり必須欠落で 400 の恐れ。スコープ済み(classNameId 指定)で開けば classCategoryName/classNameId とも揃うため通る見込み。成功フラッシュ描画は要確認。 |
| 決定ボタン (インライン編集 mode-edit doUpdateClassCategory form) | 既存分類の名前を変えて『決定』を押す | POST /admin/class-category/class-category?classCategoryId=<id>&_method=put — csrfToken=..., classCategoryName=<編集値> | 303 redirect 後に一覧で名称が更新表示。EC-CUBE: 『規格分類を編集しました』。 | ⚠️ リダイレクト先リソース /admin/class-category/class-category (単票 PUT) は本グループ外。存在/PUT 実装の有無を確認しないと no-op/404 リスク。mode-edit は既定で d-none、表示には action-edit(JS) が必要 — no-JS では編集 form が出ず『決定』に到達不能 (JS 依存)。 |

#### 規格名CSVダウンロード (Page/Admin/ClassName/ClassNameExport) — var/templates/Page/Admin/ClassName/ClassNameExport.html.twig

`/admin/class-name/class-name-export` ／ src/Resource/Page/Admin/ClassName/ClassNameExport.php (onGet ダウンロード)　<br>前提: authenticated admin。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 規格CSV登録ボタン (csv textarea + submit) — 同居インポート form | CSV 本文を貼って『規格CSV登録』を押す | POST /admin/product/csv-class-name — csv=<textarea本文> | 303 redirect to /admin/class-name/class-name-list、『規格名CSVを取り込みました。』。一覧に反映。 | ⚠️ ページ自体は GET エクスポート専用 (CSV ダウンロード)。import form は CsvClassName::onPost(csv) に csv テキストを正しく送るためフィールド名一致。取込の実永続化は要確認。GET ダウンロードはファイルが落ちれば成功。 |

#### 規格名一覧 (Page/Admin/ClassName/ClassNameList) — var/templates/Page/Admin/ClassName/ClassNameList.html.twig

`/admin/class-name/class-name-list` ／ src/Resource/Page/Admin/ClassName/ClassNameList.php (onGet 一覧, onPost 作成)　<br>前提: authenticated admin。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 新規作成ボタン (doCreateClassName form, form.input('classNameLabel')) | 規格名を入力して『新規作成』を押す | POST /admin/class-name/class-name-list — csrfToken=..., classNameLabel=<入力> | 303 redirect to /admin/class-name/class-name-list、一覧に新規格名が追加表示。EC-CUBE: 『規格名を登録しました』。 | ⚠️ フィールド名一致(classNameLabel)で 400 リスク低。HtmlMutationResponse が CREATED を 303 に変換し同 list へ戻す。一覧再描画で新行が見えれば成功。成功フラッシュ描画は要確認。 |
| 決定ボタン (インライン編集 mode-edit doUpdateClassName form, name=classNameLabel) | 既存規格名を変えて『決定』を押す | POST /admin/class-name/class-name?classNameId=<id>&_method=put — csrfToken=..., classNameLabel=<編集値> | 303 redirect 後に一覧で名称が更新表示。EC-CUBE: 『規格名を編集しました』。 | ⚠️ リダイレクト/更新先 /admin/class-name/class-name (単票 PUT) は本グループ外。実装の有無を確認しないと no-op/404。mode-edit は d-none 既定で action-edit(JS) が無いと編集 form が現れず『決定』不能 — JS 依存。 |
| 削除リンク (一覧行『削除』 → 削除モーダル → 確認 <a data-post-action=delete data-method=delete>) | 行の削除アイコン→モーダルで削除確定 | anchor href=/admin/class-name/class-name?classNameId=<id>&_method=delete (data-url からモーダルへ注入), data-method=delete, csrf_token_for_anchor('...') | 対象規格名行が一覧から消える / 303 redirect to /admin/class-name/class-name-list | ⚠️ 削除はモーダル(JS, data-bs-toggle)→ data-method=delete を eccube.js が POST(_method=delete) に変換する前提の JS 依存アフォーダンス。no-JS ではモーダルも DELETE も発火しない。削除先リソース /admin/class-name/class-name(DELETE) も本グループ外で要存在確認。 |

#### 商品一覧(管理) (Page/Admin/ProductList) — var/templates/Page/Admin/ProductList.html.twig

`/admin/product-list` ／ src/Resource/Page/Admin/ProductList.php (onGet 検索/一覧のみ)　<br>前提: authenticated admin。商品が数件存在。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 検索ボタン (search_form, method=get, searchForm.input('nameKeyword')) | キーワードを空のまま、または入力して『検索』を押す | GET /admin/product-list?nameKeyword=(空 もしくは 入力値) | 一覧が再描画され、キーワードに一致する商品行だけが表示される (空なら全件)。検索ボックスに入力値が再表示される。 | ⚠️ onGet は nameKeyword=null 既定 + limit=50/offset=0 で空キーワードでも 400 にならない設計。トップページ検索の空 category_id で 400 した事例とは異なり、ここはガードあり。実機で空検索が全件を返し、一致絞り込みが効くか確認。 |
| 一括公開/非公開/廃止ボタン (action-submit doBulkUpdateProductStatus, type=button, data-action=/admin/product-bulk-status) | 商品行のチェックボックスを選び一括ステータスボタンを押す | JS が form#form_bulk に productCodes[]=<選択コード> を集め action=/admin/product-bulk-status へ submit (data-product-status と CSRF を JS で付与) | 303 redirect to /admin/product-list、対象商品の公開ステータス列が新値に変わって再表示。EC-CUBE: 『商品を一括変更しました』。 | ⚠️ JS 依存。ボタンは type='button' + data-action で、jQuery (#form_bulk .action-submit click ハンドラ) が submit する。no-JS では何も起きない。さらに data-product-status はサーバが受ける status パラメータと対応が必要だが ProductBulkStatus::onPost のシグネチャは productCodes 配列ベース — status 値の受け渡し名/CSRF token-for-anchor の解決を実機で確認。送信が成功すれば一覧に反映。 |
| 削除(一括) ボタン (btn-ec-delete → bulkDeleteModal → #bulkDelete) | チェックした商品を一括削除モーダルで確定 | JS ループ: 各 checkbox の data-delete-url=/admin/product?productCode=<code>&_method=delete を $.ajax で順次 DELETE (token-for-anchor=csrf_token_for_anchor('admin_product_delete')) | 削除した商品行が一覧から消え、モーダルに『商品の削除処理が完了しました』表示。各 DELETE は Product::onDelete → /admin/product-list へ 303。 | ⚠️ 完全に JS 依存 (type=button + $.ajax ループ + data-delete-url)。no-JS では削除不能。Product::onDelete はソフト削除(status=3)で body に message を返すが、JS 側が成功判定するレスポンス形(JSON)と HtmlMutationResponse の 303 が噛み合うか要確認 — AJAX が 303 を受けて 'failed' 判定する恐れ。実機で削除完了表示まで確認必須。 |

#### 商品編集/登録エディタ (Page/Admin/Product/Edit) — var/templates/Page/Admin/Product/Edit.html.twig

`/admin/product/edit  (and /admin/product/edit?productCode=<code>)` ／ src/Resource/Page/Admin/Product/Edit.php (onGet のみ; POST は Admin\Product リソース)　<br>前提: authenticated admin (adminId 無なら 403 + 『この操作には管理者ログインが必要です。』)。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (productCode 隠し + 各 form.input マクロ, submit) | 新規(空 productCode)で各項目を入力し『登録』、または既存編集して『登録』 | POST /admin/product (新規) または POST /admin/product?_method=put (既存) — productCode=<空 or code>, productName, productStatus, description, searchWord, note, price02, stock | 新規: 303 redirect to /admin/product?productCode=<新code> (CREATED→303)。既存: 303 redirect to /admin/product?productCode=<code> (OK→303)。保存後に同商品の値が反映表示。EC-CUBE: 『商品を登録/編集しました』。 | ⚠️ リダイレクト先 /admin/product は Admin\Product リソースの GET (JSON 単票ボディ) で HTML 商品エディタに戻らない恐れ — 保存後にユーザーが空白/JSON 画面を見る可能性。フィールド名(productName/price02/stock/productStatus/...)は onPost/onPut と一致し 400 リスク低。stock 空欄は ''→null 正規化済み。成功フラッシュの表示元を実機確認。 |

#### 商品規格マトリクス (Page/Admin/Product/ProductClass) — var/templates/Page/Admin/Product/ProductClass.html.twig

`/admin/product/product-class?productCode=<code>` ／ src/Resource/Page/Admin/Product/ProductClass.php (onGet 空エディタ, onPost 1行登録)　<br>前提: authenticated admin (adminId 無なら 403)。productCode を持つ商品。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (doRegisterProductClass form, submit) | 価格/在庫/在庫無制限/商品コード/送料 を入力して『登録』を押す | POST /admin/product/product-class — productCode=<code>, product_code=<入力>, price02=<入力>, stock=<入力>, stock_unlimited=<chk>, delivery_fee=<入力> | 303 redirect to /admin/product/product-class?productCode=<code>、登録した規格行(SKU)がマトリクスに表示。EC-CUBE: 『商品規格を登録しました』。 | ⚠️ フィールド名不一致の懸念: テンプレ form は snake_case (product_code, stock_unlimited, delivery_fee) を送るが onPost のパラメータは camelCase (stockUnlimited, deliveryFee) で price02/productCode のみ一致。stockUnlimited/deliveryFee がバインドされず既定値(false/0)に落ちる恐れ — 送料/在庫無制限がユーザー入力どおりに保存されない。さらに onGet は classes=[] 固定の空エディタで、登録後に既存 SKU 一覧を READ する手段が無い(domain に read 無し)ため、リダイレクト後もマトリクスが空のままで『登録できたか分からない』。FLAG。 |

#### 商品登録フォーム (Page/Admin/ProductNew) — var/templates/Page/Admin/ProductNew.html.twig

`/admin/product/new (ProductNew GET)` ／ src/Resource/Page/Admin/ProductNew.php (onGet のみ; POST は Admin\Product)　<br>前提: authenticated admin (adminId 無なら 403)。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (商品登録 form, submit) | 商品コード/商品名/価格/在庫/ステータス/説明等を入力して『登録』を押す | POST /admin/product — productCode=<入力>, productName=<入力>, price02=<入力>, stock=<入力 or 空>, productStatus=<選択値>, description, searchWord, note (csrfToken=issue 済み) | 303 redirect to /admin/product?productCode=<新code> (CREATED→303)。作成商品の詳細/一覧で表示。EC-CUBE: 『商品を登録しました』。 | ⚠️ フィールド名(productCode/productName/price02/...)は onPost と一致。productName/price02 は必須なので空送信で 400(field error 表示が必要)。productCode 重複は 409 (ProductCodeAlreadyInUse) — 画面にエラー表示されるか要確認。リダイレクト先 /admin/product GET は JSON 単票で HTML に戻らない恐れ。成功フラッシュ表示元を実機確認。 |

#### タグ一覧 (Page/Admin/Tag/TagList) — var/templates/Page/Admin/Tag/TagList.html.twig

`/admin/tag/tag-list` ／ src/Resource/Page/Admin/Tag/TagList.php (onGet 一覧, onPost 作成)　<br>前提: authenticated admin。CSRF: csrf_token('admin_tag')。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 新規作成ボタン (doCreateTag form, form.input('tagName')) | タグ名を入力して『新規作成』を押す | POST /admin/tag/tag-list — csrfToken=..., tagName=<入力> | 303 redirect to /admin/tag/tag-list、一覧に新タグが追加表示。EC-CUBE: 『タグを登録しました』。 | ⚠️ フィールド名一致(tagName)で 400 リスク低。CREATED→303 で同 list へ戻す。一覧再描画で新行が見えれば成功。成功フラッシュ描画は要確認。 |
| 決定ボタン (インライン編集 mode-edit form, name='name') | 既存タグ名を変更して『決定』を押す | POST /admin/tag/tag-list — csrfToken=..., name=<編集値> | 対象タグの名称が更新されて一覧再表示。EC-CUBE: 『タグを編集しました』。 | ⚠️ 失敗確度高。編集 form は同じ /admin/tag/tag-list に name=<値> を POST するが、TagList::onPost は tagName(必須) のみ受け、name パラメータも PUT/更新ロジックも存在しない。結果: tagName 欠落で 400、もしくは(name 無視で)別の必須エラー。リネームが実行されない契約ズレ。さらに mode-edit は d-none 既定で action-edit(JS) が無いと編集欄が出ず『決定』に到達不能 — JS 依存。 |
| 削除リンク (一覧行『削除』 → DeleteModal → 確認 <a data-post-action=delete data-method=delete>) | 行の削除アイコン→モーダルで削除確定 | anchor href=/admin/tag/tag?tagId=<id> (data-url からモーダルに注入), data-method=delete, csrf_token_for_anchor('admin_tag_delete') | 対象タグ行が一覧から消える / 303 redirect to /admin/tag/tag-list | ⚠️ JS 依存 (Bootstrap モーダル + data-method=delete を eccube.js が _method=delete POST に変換する前提)。no-JS では削除不能。削除先 /admin/tag/tag(DELETE) は本グループ外で要存在確認。data-url が _method=delete を含まない (/admin/tag/tag?tagId=...) ため、変換 JS が無いと単なる GET ナビになり削除されない。 |

### admin-order


#### 受注詳細/編集 (Page/Admin/Order — var/templates/Page/Admin/Order.html.twig)

`/admin/order?orderNo={orderNo}` ／ src/Resource/Page/Admin/Order.php　<br>前提: authenticated admin; an existing order whose orderNo is known (e.g. seed order 'past0000000000000000000000000001'). GET /admin/order?orderNo=... must render the detail.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 保存ボタン (type=submit, form#order_form) | 値引き/手数料/利用ポイントを変更せず（または変更して）「保存」を押し、POST + _method=put で送信する | _method=put, csrfToken={{csrfToken}}, orderNo={{orderNo}}, discount={{discount}}(既定=現在値, 0なら'0'), charge={{charge}}(既定=現在値), usePoint={{usePoint}}(既定=現在値) | EC-CUBE 忠実: 303 redirect → /admin/order?orderNo={orderNo} (admin_order_edit) し、再表示画面の上部に flash success 「保存しました」(admin.common.save_complete) が出る | ⚠️ FAIL: onPut は Code::OK を直接セットし Location も成功メッセージも返さない（MutationResponse 不使用）。html context でも 200 のまま、テンプレートには成功メッセージ領域が一切無く（grep で message/success/alert 0件）、ユーザーは保存できたか判別不能。さらに POST は同 URL に戻らず POST-result body を返すため再読込で再POSTの危険 |
| 保存ボタン — 空 orderNo / 不正 CSRF の境界 | orderNo を空、または期限切れ csrfToken で「保存」を送る | _method=put, csrfToken=(空/不正), orderNo=(空), discount=0, charge=0, usePoint=0 | 不正CSRF → 403 表示; orderNo 形式不正 → 400 のフィールド/全体エラー表示 | ⚠️ onPut は #[CsrfToken] 有り→不正CSRFで 403、SemanticVariableException→400 にマップされる想定だが、テンプレートにエラー表示領域が無いため 400/403 が生 JSON か白画面で返り、ユーザーに何が悪いか伝わらない可能性 |

#### 受注登録/受注編集エディタ (Page/Admin/Order/Edit — Order/Edit.html.twig)

`/admin/order/edit?orderNo={orderNo}` ／ src/Resource/Page/Admin/Order/Edit.php　<br>前提: authenticated admin。orderNo 空=新規登録、既知 orderNo=プリフィル。GET のみ実装（onGet）。POST 先 /admin/order/create は別リソース。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録/保存ボタン (type=submit, form#order_form) | 新規時は「登録」(action=/admin/order/create)、編集時は「保存」(action=/admin/order, _method=put) を押す | _method=put(編集時のみ), orderNo={{orderNo\|default('')}}, customerId=(空), paymentMethodId=1, orderItems[0][productCode]=(空), orderItems[0][productName]=(空), orderItems[0][unitPrice]=0, orderItems[0][quantity]=1, deliveryFeeTotal=0, name01/name02/email/message=(form macro既定=空), charge/discount/usePoint=0 | EC-CUBE 忠実: 登録/保存後 303 → admin_order_edit へ redirect し flash「保存しました」表示。明細未入力なら明細欄に field-level バリデーションエラー「商品を入力してください」等 | ⚠️ FAIL(新規): action=/admin/order/create に対応する Edit リソースは onGet のみで onPost が無い。create リソースの存在/実装は本グループ外だが、新規登録ボタンは実質受け先が無い恐れ。FAIL(編集): action=/admin/order の onPut は discount/charge/usePoint しか受け付けず（mass-assignment 制限）、customerId/orderItems/name01 等の送信フィールドは全て無視され保存されない。ユーザーが編集したつもりの商品明細・顧客が反映されないサイレント・データ欠落 |
| 非管理者アクセス境界 | 管理者未ログインで GET /admin/order/edit を開く | (GET, body無し) | 403 + 「この操作には管理者ログインが必要です。」 | none（onGet が adminSession.adminId===null で 403+message を返す） |

#### 出荷CSVダウンロード (Page/Admin/Order/ExportShipping — Order/ExportShipping.html.twig)

`/admin/order/export-shipping` ／ src/Resource/Page/Admin/Order/ExportShipping.php　<br>前提: authenticated admin。GET は CSV(text/csv) を返す。テンプレートは import-shipping への小フォーム。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 出荷CSV登録ボタン (type=submit, action=/admin/order/import-shipping, enctype=multipart/form-data) | 「出荷CSV登録」を押して /admin/order/import-shipping に遷移する | csv=(textarea, 既定=空/null) | /admin/order/import-shipping の出荷CSV登録(アップロード)画面に遷移し、その画面が表示される | ⚠️ FLAG: このフォームは POST で textarea name=csv を送るが、遷移先 ImportShipping.onPost は csv(string) を受けるものの、本来のアップロード画面(ImportShipping.html.twig)はファイル input(import_file)を使う別UI。ここで送る空 csv は minLength:0 を通過し 0件インポートで成功扱いになりうる（後述 ImportShipping 参照）。また GET /export-shipping 自体は Content-Type text/csv を返すため、リンク表示ではなくブラウザがCSVダウンロードする点を確認 |

#### 出荷CSV登録/アップロード (Page/Admin/Order/ImportShipping — Order/ImportShipping.html.twig)

`/admin/order/import-shipping` ／ src/Resource/Page/Admin/Order/ImportShipping.php　<br>前提: authenticated admin。GET=アップロードフォーム表示、POST=CSV取込。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (type=submit, form#csv_shipping_form, action=/admin/order/import-shipping?csv=, enctype=multipart/form-data) | CSVファイルを選んで「登録」を押す | import_file=(type=file, 既定=null) ＋ URL クエリ csv=(空) | EC-CUBE 忠実: 取込完了後 303 → /admin/order-list へ redirect し flash「CSVファイルをアップロードしました」(admin.common.csv_upload_complete) と取込件数を表示 | ⚠️ FAIL(フィールド名不一致): フォームは import_file(file) を送るのに、onPost が受けるのは csv(string)。action のクエリ csv= は空。よって onPost には空文字 csv が渡り、param schema は minLength:0 を許容するため 400 にならず『成功』として 0件取込→ /admin/order-list へ redirect。ユーザーは実際のファイル内容が一切取り込まれないのに成功に見える（サイレント no-op）。imported=0/skipped=0 はメッセージ表示されない限り判別不能 |
| 未管理者/不正CSRF境界 | 管理者未ログイン、または不正 CSRF で登録 | import_file=..., csrfToken=(不正) | 管理者未ログイン → 403「この操作には管理者ログインが必要です。」; 不正CSRF → 403 | ⚠️ onGet は adminSession 直チェックで 403 を返すが、onPost には明示の adminSession チェックが無く #[CsrfToken] と Be Final の UnauthorizedAdminAccessException 依存。403 到達は要確認 |

#### 受注メール確認 (Page/Admin/Order/MailConfirm — Order/MailConfirm.html.twig)

`/admin/order/mail-confirm?orderNo={orderNo}` ／ src/Resource/Page/Admin/Order/MailConfirm.php　<br>前提: authenticated admin。SendMail 構成画面からの確認ステップ（読み取り専用プレビュー）。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 送信するボタン (type=submit, form#mail_confirm_form, action=/admin/order/send-mail?orderNo=...) | プレビュー確認後「送信する」を押し、send-mail に POST する | orderNo={{orderNo}}, mode=complete | EC-CUBE 忠実: メール送信後 303 → admin_order_edit へ redirect し flash「メールを送信しました。」(admin.order.mail_send_complete) | ⚠️ FAIL: 遷移先 SendMail.onPost は orderNo しか受けず（mode は無視）、Code::OK + JSON body（message:'注文確認メールを再送しました。'）を返すのみで Location 無し・redirect 無し。html context でこの JSON が画面に出ず、ユーザーは送信できたか分からない。さらに MailConfirm は mode=complete を送るが onPost 側にその分岐は無い |

#### 帳票(納品書PDF)出力 (Page/Admin/Order/OrderPdf — Order/OrderPdf.html.twig)

`/admin/order/order-pdf?orderNo={orderNo}` ／ src/Resource/Page/Admin/Order/OrderPdf.php　<br>前提: authenticated admin。GET=PDFオプションフォーム表示のみ（onGet）。実生成は別リソース ExportOrderPdf（Phase2 スタブ）。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| PDF出力ボタン (type=submit, form#order_pdf_form, action=/admin/order/export-order-pdf) | タイトル/挨拶文/印刷日を入力し「PDF出力」を押して export-order-pdf に POST する | orderNo={{orderNo\|default('')}}, title=納品書, message1/2/3=(既定の挨拶文), note1/2/3=(空), issue_date=(空) | application/pdf がストリームされ、ブラウザが納品書PDFをダウンロード/表示する | ⚠️ FLAG: 遷移先 ExportOrderPdf は本グループの設計コメント上 Phase2 スタブ。本グループに onPost 実装が無く、実PDFが生成されない/空PDFの恐れ。OrderPdf 自身は GET フォーム表示までで観測可能シグナル無し（ボタン押下までは正常） |

#### 受注メール送信(構成) (Page/Admin/Order/SendMail — Order/SendMail.html.twig)

`/admin/order/send-mail?orderNo={orderNo}` ／ src/Resource/Page/Admin/Order/SendMail.php　<br>前提: authenticated admin。GET=メール構成フォーム、POST=注文確認メール再送。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 確認画面へボタン (type=button, id=preview_button, data-bs-toggle=modal) — submit:false | 件名/本文を入力し「確認画面へ」を押す | (JS): モーダルを開くのみ。フォーム送信しない。フォーム本体は template={{}}, mail_subject, mail_header, mail_footer(form macro) | 送信内容プレビューのモーダルが開く | ⚠️ FLAG(JS依存): type=button + data-bs-toggle のみで、JS無効時は何も起きない。no-JS で確認画面に進めず送信フローが詰む |
| 送信ボタン (モーダル内 type=submit, form=mail_form) | モーダルで内容確認後「送信」を押し、send-mail に POST する | orderNo={{orderNo}}, template=(form), mail_subject=(form), mail_header=(form), mail_footer=(form) | EC-CUBE 忠実: 303 → admin_order_edit へ redirect し flash「メールを送信しました。」 | ⚠️ FAIL: onPost は orderNo のみ受け取り、入力した mail_subject/mail_header/mail_footer/template は全て無視（resource signature が orderNo 単独、設計コメントも『custom subject/body は Wave9η 未配線』と明記）。ユーザーが編集した件名・本文は送られず固定の注文確認メールが再送される。加えて Code::OK + JSON body のみで redirect/flash 無し→送信成功がHTML画面に出ない |

#### 出荷登録/配送先編集 (Page/Admin/Order/ShippingAddress — Order/ShippingAddress.html.twig)

`/admin/order/shipping-address?orderNo={orderNo}` ／ src/Resource/Page/Admin/Order/ShippingAddress.php　<br>前提: authenticated admin。GET=配送先フォーム(空表示)、PUT=上書き、POST=住所録から選択。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (type=submit, form#shipping_form, _method=put → doUpdateShippingAddress) | 氏名/郵便番号/都道府県/住所/電話を入力し「登録」を押す | _method=put, csrfToken, orderNo={{orderNo\|default('')}}, name01, name02, postalCode, pref(form macro既定=未選択/空), addr01, addr02, phoneNumber | EC-CUBE 忠実: 保存後 303 → admin_shipping_edit へ redirect し flash「保存しました」(admin.common.save_complete) | ⚠️ FAIL(2点): (1) onPut の $pref は非nullの int だが、フォームの pref は form.input('pref') で未選択既定が空文字を送る恐れ→空 pref='' で int 強制変換失敗→400。param schema は pref を null 許容にしているが署名は int 必須で不整合。(2) Code::OK + JSON body のみで Location/redirect/flash 無し→保存成功がHTML画面に出ない。テンプレートに success 領域も無い |
| 住所録から選択 (doSelectShippingAddress, POST + addressId) | 住所録の行を選んで適用する | orderNo, addressId=(住所録の行ID) | 選択した住所がフォームに反映され、保存後に配送先が更新される | ⚠️ FAIL: onPost は addressId(string) 必須だが、ShippingAddress.html.twig には住所録選択UI(addressId を送る POST フォーム)が見当たらず _method=put の単一フォームのみ。POST(doSelectShippingAddress) アフォーダンスが画面に投影されておらず、住所録選択がブラウザから実行不能。直接 POST すれば addressId 欠如で 400 |

#### 出荷通知メール (Page/Admin/Order/ShippingNotifyMail — Order/ShippingNotifyMail.html.twig)

`/admin/order/shipping-notify-mail?orderNo={orderNo}` ／ src/Resource/Page/Admin/Order/ShippingNotifyMail.php　<br>前提: authenticated admin。GET=確認フォーム（orderNo の注文が存在しないと 404）、POST=出荷通知メール送信。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 出荷通知メールを送信ボタン (type=submit, form#shipping_notify_mail_form, action=/admin/order/shipping-notify-mail) | 確認文を読んで「出荷通知メールを送信」を押し POST する | orderNo={{orderNo\|default('')}} | EC-CUBE 忠実: 出荷通知メール送信後、JSON {mail:true,...} または完了表示。HTML画面としては成功メッセージ「出荷通知メールを送信しました。」を表示すべき | ⚠️ FAIL: onPost は Code::OK + JSON body(message:'出荷通知メールを送信しました。') を返すが Location/redirect 無し。html context でこの JSON が画面に描画されず（テンプレートに success 領域なし）、ユーザーは送信できたか判別不能。GET の csrfToken は null 固定でフォームに hidden csrf が出ない→POST が CSRF 不正で 403 になる恐れ |
| orderNo 不正/未存在の境界 | 存在しない orderNo で画面を開く | (GET) orderNo=(未存在) | 404 + 「指定された注文は見つかりませんでした。」 | none（onGet が orders->byOrderNo===null で NOT_FOUND+message を返す） |

#### 受注一覧 (Page/Admin/OrderList — OrderList.html.twig)

`/admin/order-list` ／ src/Resource/Page/Admin/OrderList.php　<br>前提: authenticated admin。GET のみ実装。検索フォーム(GET)＋一括操作フォーム(JS)。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 検索ボタン (type=submit, id=search_submit, form#search_form, method=get action=/admin/order-list) | キーワード欄(注文番号/お名前等)に何も入れず（または入れて）「検索」を押す | multi=(form macro既定=空) ＝ GET /admin/order-list?multi= | 一覧が再描画され、上部の「検索結果：N件が該当しました」が更新される。0件なら『検索条件に合致するデータが見つかりませんでした』を表示 | ⚠️ FLAG: onGet は limit/offset しか受け付けず multi(キーワード)を完全に無視（設計コメントで multi/orderNo/customerName 等は Phase2 スコープ外と明記）。よって検索しても絞り込みが効かず、空 multi でも全件のまま。ユーザーには検索が無反応に見える。空 multi で 400 にはならない（multi はパラメータとして消費されないため）が、検索機能として未実装 |
| 一括操作ボタン群 (メールする/納品書出力/ステータス変更/CSVダウンロード/削除 — 全て type=button + JS, form#form_bulk action='' (SELF) submit:false) | 行チェックボックス ids[] を選んで「メールする」「ステータス変更」「削除」等を押す | (JS): mode=order_bulk_delete_form, filter=open, ids[]={{Order.orderNo}}, csrfToken。ボタン自体は type=button で素のsubmitをしない | EC-CUBE 忠実: 一括削除なら確認モーダル→実行後 flash「削除しました」(admin.common.delete_complete) と該当行が一覧から消える。一括ステータス変更なら JSON 応答で行のステータス表示が更新される | ⚠️ FLAG(JS依存＋受け先不在): 一括ボタンは全て type=button + data-action/JS で、no-JS では何も起きない。さらに action='' (SELF=/admin/order-list) は GET のみ実装で onPost が無く、JS が叩く想定の bulk-delete/bulk-status エンドポイント(doBulkDeleteOrder 等は #[Link] 上 forward-declare のみ)が本リソースに実装されていない。一括削除・一括メール・一括ステータスは全て実行不能の恐れ |
| 行チェックボックス / 全選択 (toggle_check_all name=filter value=open, ids[] checkbox) | ヘッダの全選択 toggle_check_all をオンにする | filter=open(checkbox), ids[]=各 Order.orderNo | 全行のチェックが入り、一括操作の対象になる | ⚠️ FLAG(JS依存): 全選択は JS(line 79〜 登録チェックボックス処理)に依存。no-JS では機能しない。filter=open の既定値は『対応中』フィルタの意味だが onGet が filter を消費しないため絞り込みに反映されない |

#### 受注対応状況設定 (Page/Admin/OrderStatus — OrderStatus.html.twig)

`/admin/order-status` ／ src/Resource/Page/Admin/OrderStatus.php　<br>前提: authenticated admin。GET=対応状況一覧フォーム、PUT=一覧更新(doUpdateOrderStatusList)、POST=単一注文のステータス変更(doUpdateOrderStatus)。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (type=submit, form#form.doUpdateOrderStatusList, method=post action=/admin/order-status?_method=put) | 対応状況の名称/色/表示順を編集し「登録」を押す | csrfToken。※フォームの送信フィールドは inventory 上 fields:[] ＝ 状況行(orderStatuses[]/orderStatusRows)を一切送らない | EC-CUBE 忠実: 保存後 303 → admin_setting_shop_order_status へ redirect し flash「保存しました」(admin.common.save_complete) と編集後の一覧表示 | ⚠️ FAIL(2点): (1) フォームが状況行データを全く送っていない（fields:[]）。onPut は orderStatuses 既定=[] で受け count=0 となり、編集内容が何も保存されない。テンプレート側の name属性が onPut の orderStatuses[]/orderStatusRows と結線されていない疑い。(2) onPut は MutationResponse 経由で 303 + Location=/admin/order-status を返す（html では SEE_OTHER）が body message は『…Resourceへ到達しました。』というスタブ文言で、本来の永続化と『保存しました』flash が無い。設計コメントも『full EC-CUBE master-data persistence yet 未claim』と明記＝未実装スタブ |
| 単一注文ステータス変更 (doUpdateOrderStatus, POST orderNo+orderStatus) — 受注詳細/一覧から呼ばれる | ある注文の対応状況を別の値（例: 5=発送済み）に変更して POST する | orderNo={対象}, orderStatus={新ステータスID 1/3/4/5/6/9}, csrfToken | EC-CUBE 忠実: ステータス変更が永続化され、一覧/詳細の対応状況表示が新しい値に更新される（JSON 応答で changed=true） | ⚠️ FLAG: onPost は Code::OK + JSON body(changed) を返すが Location/flash 無し。html context では成功が画面に描画されない。また OrderStatus.html.twig には単一注文ステータス変更フォームが無く（一覧更新フォームのみ）、この POST を出すUIは受注一覧(JS bulk)や受注詳細側に依存。冪等再送時 changed=false は無表示で判別不能 |

### admin-customer


#### 会員一覧 (Page/Admin/CustomerList) — var/templates/Page/Admin/CustomerList.html.twig

`/admin/customer-list` ／ src/Resource/Page/Admin/CustomerList.php (onGet only)　<br>前提: authenticated admin (AdminSession.adminId set). Without it the Be Final raises UnauthorizedAdminAccessException -> 403. Seed: at least one customer row so the list/table branch renders.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 検索ボタン (search submit, type=submit, label 検索) | Open /admin/customer-list and click 検索 WITHOUT typing anything into the keyword box (the default, empty-keyword search — exactly the empty-default case that bit the top-page). | GET /admin/customer-list with multi=(空文字). Only field on the form is searchForm.input('multi'); its default value is empty. No nameKeyword/emailKeyword/limit are sent by the browser. | Page re-renders (200) listing ALL customers; the count line '検索結果：N件が該当しました' shows N>0 and the table lists every customer row. Empty keyword = match-all, NOT an error. | none for HTTP status: get-admin-customer-list.param.json has no required[] and multi minLength=0, so multi='' passes schema and onGet maps multi->nameKeyword. Verify match-all semantics in GetCustomerListInput actually return all rows for an empty/blank keyword (a naive LIKE %%''%% is fine, but a guard that treats ''as 'no filter' vs 'filter on empty string' must yield all rows — confirm the count is the full seed count, not 0). |
| 検索ボックス + 検索ボタン (keyword 'multi') | Type a known customer name or email fragment into the 会員ID・メールアドレス・お名前 box and click 検索. | GET /admin/customer-list with multi=<typed keyword> (e.g. multi=alice). Single real field 'multi'. | List re-renders showing only matching rows; count line updates to the matched count; the keyword box stays pre-filled with the typed value (searchForm.fillFilters re-shows it). With a no-match keyword, the else-branch '検索条件に合致するデータが見つかりませんでした' card shows and count=0. | none expected. Note: onGet only wires multi->nameKeyword; an EMAIL typed into multi is matched via nameKeyword substring on name fields — confirm GetCustomerListInput's nameKeyword path also covers email (EC-CUBE's admin_search_customer_multi searches id+email+name). If it only searches name, an email keyword silently returns 0 — a faithfulness gap to flag. |
| 詳細検索パネル (会員種別/性別/誕生月/購入金額/電話番号/各日付レンジ等) | Expand 詳細検索 and attempt to use any of the detail filters (status, sex, prefecture, date ranges, purchase amount). | Nothing usable — these widgets render EMPTY (AdminCustomerSearchForm declares only 'multi'; the detail-panel rows are static labels with empty <div> cells, no <input>/<select>). | EC-CUBE-faithful: each detail filter is an interactive control that narrows the result. CURRENT: the controls do not exist, so the user can fill nothing and the search ignores all detail axes. | ⚠️ FLAG (enumerated residual, but a real UX defect): the detail-search panel is decorative — labels with no inputs. A user opening 詳細検索 sees fields they cannot fill. Not a 400, but the affordance is non-functional vs EC-CUBE. |
| 削除アイコン + 削除確認モーダル (per-row, 削除) | Click the close (x) action icon on a customer row, then click 削除 in the '削除します' modal. | No form POST. The x icon is <a data-bs-toggle="modal"> (JS-only, opens the Bootstrap modal). The 削除 confirm is a plain <a href="/admin/delete-customer?customerId=..."> — a GET navigation to a DIFFERENT resource (Page/Admin/DeleteCustomer, outside this screen). | EC-CUBE: row deletion via DELETE method + 'admin.common.delete_complete' flash, then redirect back to the list with the row gone. | ⚠️ FLAG (JS-dependency + method mismatch): (1) With JS disabled the modal never opens, so delete is unreachable. (2) The confirm link is a GET <a> to /admin/delete-customer, not a DELETE — EC-CUBE uses a DELETE form with CSRF. A GET-triggered destructive delete is a CSRF/faithfulness concern. (3) Target resource is out of this group; verify it exists and redirects back to /admin/customer-list with the row removed and a delete-success signal (which, like saves, is likely NOT rendered — see save-flash risk below). |
| CSVダウンロード / CSV出力項目設定 links (shown when count>0) | Click CSVダウンロード or CSV出力項目設定. | GET navigation to /admin/customer-csv or /admin/csv-config (plain <a>). | EC-CUBE: a CSV download stream / the CSV-config screen. | ⚠️ FLAG/out-of-scope: targets are separate resources not in this group; verify they are not stub 404s. Listed for completeness. |

#### 会員登録/編集 (Page/Admin/Customer) — var/templates/Page/Admin/Customer.html.twig

`/admin/customer?customerId=<id>` ／ src/Resource/Page/Admin/Customer.php (onGet renders form pre-filled; onPost = doUpdateCustomerProfile)　<br>前提: authenticated admin; an existing customer whose customerId is passed in the query. onGet with no email/customerId/id returns 400 '会員IDまたはメールアドレスを指定してください。'. EDIT mode only (resource always resolves an existing customer).


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (save submit, type=submit, label 登録) | Open the edit page for an existing customer (fields pre-filled), change お名前 (name01/name02), leave the rest at their pre-filled defaults, and click 登録. | POST /admin/customer?customerId=<id> with csrfToken=<token>, customerId=<id> (hidden), name01=<edited>, name02=<edited>, kana01, kana02, company_name, postal_code, pref, addr01, addr02, email=<prefilled>, phone_number, plain_password_first=(空), plain_password_second=(空), sex, job, birth, point, note. NOTE the REAL submitted names are snake_case (company_name, postal_code, phone_number, plain_password_first/second), NOT the camelCase the resource expects. | EC-CUBE-faithful: 303 redirect (POST-redirect-GET) to /admin/customer?customerId=<id>, the reloaded edit page shows the NEW name values, AND a success banner 'admin.common.save_complete' (保存しました) is shown. | ⚠️ FLAG, multiple: (1) NO SUCCESS SIGNAL — HtmlMutationResponse returns 303 to /admin/customer?customerId=<id>, but admin-base.html.twig DROPS the EC-CUBE flash include (enumerated residual). The redirected GET renders the form with updated values but NO '保存しました' banner — the user cannot positively tell the save worked, only that the name field now shows the new value. EC-CUBE shows the flash; BeMart does not. (2) FIELD-NAME MISMATCH: the browser submits company_name/postal_code/phone_number/plain_password_first, but Customer::onPost params are companyName/postalCode/phoneNumber/password. Those edits will NOT bind (params stay null) and are SILENTLY DROPPED — editing the company name, postal code, phone, or password and saving appears to succeed but does not persist those fields. (3) point/note/sex/job/birth: onPost has no 'point' or 'note' param at all, so those edits are dropped entirely. |
| 登録ボタン — clear required お名前 then save | Clear name01 (お名前 姓, badged 必須) and click 登録. | POST /admin/customer?customerId=<id> with name01=(空), name02=<prefilled>, email=<prefilled>, customerId=<id>, csrfToken=<token>, plus the rest. | Field-level validation error rendered inline under name01 via form.error('name01') (e.g. 'お名前(姓)を入力してください'); the page stays on the form, no redirect, no persistence. | ⚠️ FLAG: post-admin-update-customer.param.json marks name01 required but with minLength=0, so an empty-string name01 PASSES the transport schema and reaches the Be chain. Verify the Be Becoming chain (SemanticVariableException) actually rejects empty name01 AND that the resulting error is mapped back into form.error('name01') for inline display. If the Be error surfaces only as a top-level {message} (rendered by the bottom '{% if message %}' ec-errorMessage block) rather than per-field, the inline 必須 feedback the template wires up will be empty — a degraded but still observable error. If empty name01 is accepted (no rejection), that is a defect. |
| 登録ボタン — invalid email then save | Change メールアドレス to a malformed value (e.g. 'not-an-email') and click 登録. | POST with email=not-an-email plus the other fields. | Inline field error under email via form.error('email') (メールアドレスの形式が不正です。 / equivalent), no redirect, no persistence. | ⚠️ FLAG: onPost does NOT pre-validate email format (unlike onGet which filter_vars it); it passes email straight into AdminUpdateCustomerInput. Verify the Be chain raises SemanticVariableException for a malformed email and that it maps to an inline form.error('email') (or at minimum the bottom ec-errorMessage block) rather than a 500 or a silent accept. |
| 登録ボタン — change password | Type a new password into パスワード and パスワード(確認用) and click 登録. | POST with plain_password_first=<new>, plain_password_second=<new> (the REAL field names). | EC-CUBE-faithful: password is updated; redirect + 保存しました; subsequent login uses the new password. | ⚠️ FLAG: onPost's param is 'password' (single), but the form submits plain_password_first / plain_password_second. Neither binds to 'password' -> the new password is SILENTLY IGNORED. Also there is no first==second confirmation check wired to the form. The user appears to change the password but it does not take effect. |
| 性別 / 職業 / 都道府県 selects (form.input('sex'\|'job'\|'pref')) | Change 性別 (radio), 職業 (select), or 都道府県 (select) and click 登録. | POST with sex=<int>, job=<int>, pref=<int> (these names DO match onPost params sex/job/pref). | After redirect the reloaded form shows the newly selected option pre-selected (the persisted value re-fills the control). | ⚠️ Partial: names match so binding works. BUT verify the form's select/radio option SETS are actually populated — the Customer.html.twig docblock notes the 公開状態 status select was OMITTED for lack of an option set; confirm sex/job/pref render real <option>s (from mtb_sex / mtb_job / mtb_pref) and not empty selects. An empty <select> means the admin cannot pick a value at all. |
| お届け先住所を追加 link | Click お届け先住所を追加 in the お届け先住所 card. | GET navigation to /admin/customer-delivery-edit?customerId=<id> (plain <a>). | Navigates to the delivery-address editor (CustomerDeliveryEdit screen) with customerId carried. | none for navigation. NOTE: the address book itself always renders the empty-state '<span>この会員のお届け先がありません</span>' — the AdminCustomerFetched projection does not carry the address list (FLAGGED in template). So existing addresses are never shown here, and there is no in-page edit/delete affordance for them. |
| 会員一覧 back link | Click 会員一覧. | GET /admin/customer-list. | Returns to the customer list. | none. |

#### お届け先編集 (Page/Admin/CustomerDeliveryEdit) — var/templates/Page/Admin/CustomerDeliveryEdit.html.twig

`/admin/customer-delivery-edit?customerId=<id> (create) or ?customerId=<id>&addressId=<aid> (edit)` ／ src/Resource/Page/Admin/CustomerDeliveryEdit.php (onGet renders form; onPost create/update; onDelete remove)　<br>前提: authenticated admin (onGet returns 403 '管理者ログインが必要です。' when AdminSession.adminId is null); a target customerId in the query. For update/delete, an existing addressId belonging to that customer.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン — CREATE new address (addressId empty) | Open /admin/customer-delivery-edit?customerId=<id> (no addressId), fill お名前/郵便番号/都道府県/住所/電話番号, click 登録. | POST to '' (self) with csrfToken=<token>, customerId=<id> (hidden), addressId=(空) (hidden), name01, name02, kana01, kana02, company_name, postal_code, pref, addr01, addr02, phone_number. REAL names are snake_case: postal_code / phone_number / company_name. | EC-CUBE-faithful: 303 redirect to /admin/customer?customerId=<id>; the member edit page shows the new address in お届け先住所 with a 保存しました banner. | ⚠️ FLAG, multiple: (1) FIELD-NAME MISMATCH on REQUIRED fields: onPost requires postalCode and phoneNumber (post-admin-customer-delivery-edit.param.json required[] includes postalCode, pref, phoneNumber, addr01, addr02, name01, name02), but the form submits postal_code and phone_number. These will NOT bind -> the required params are MISSING -> 400 Bad Request, even though the user filled the visible fields. This screen likely FAILS to create an address from the real form. (2) Even on success, NO save-success banner is rendered (admin-base drops the flash include) AND the redirect target /admin/customer never displays the address (empty-state only, per Customer.html.twig) — so the user has no observable confirmation the address was created. (3) addr01/addr02 names match; pref name matches. |
| 登録ボタン — UPDATE existing address (addressId present) | Open the editor with ?customerId=<id>&addressId=<aid>, change a field, click 登録. | POST to '' (self) with addressId=<aid> (hidden, non-empty), customerId=<id>, csrfToken, plus name01/name02/postal_code/pref/addr01/addr02/phone_number/... | 303 redirect to /admin/customer?customerId=<id> with the address updated; 保存しmany banner (EC-CUBE). | ⚠️ FLAG: (1) The onGet does NOT read addressId from the query (signature is onGet(customerId, id) only) and the form's addressId hidden binds {{ addressId\|default('') }}, which is never set by onGet's body -> the hidden addressId is ALWAYS empty on render. Therefore the UPDATE branch (addressId present) is UNREACHABLE from this rendered form: every submit goes to the CREATE branch. Editing an existing address actually creates a duplicate. (2) Same postal_code/phone_number snake_case vs camelCase required-param mismatch as create -> 400 risk. (3) No success banner. |
| 登録ボタン — clear required field | Leave お名前 (name01) or 郵便番号 empty and click 登録. | POST with name01=(空) or postal_code present-but-camel-mismatched; required transport params possibly missing. | Field-level validation error shown inline under the field (form.error('name01') is wired for name01/name02 only); page stays, no redirect. | ⚠️ FLAG: only name01/name02 have inline form.error() rendering in this template; 郵便番号/都道府県/住所/電話番号 have NO {% if form.error(...) %} block, so their validation failures (or the binding-mismatch 400s) surface with no inline message — the user sees a 400/blank with no on-field explanation. Confirm whether a top-level error region exists (this template has none — unlike Customer.html.twig there is no bottom ec-errorMessage block), meaning a rejected delivery save may render as a bare 400 page with no message at all. |
| お届け先の削除 (doDeleteCustomerDeliveryAddress / onDelete) | Attempt to delete an existing delivery address from the UI. | Nothing — there is NO delete form or button anywhere. CustomerDeliveryEdit.html.twig has only the create/update form; Customer.html.twig renders the address book as empty-state with no per-address delete <a>/form. The resource's onDelete (DELETE customerId+addressId) is unreachable from any rendered template. | EC-CUBE-faithful: a 削除 control per address that issues DELETE, removes the row, shows 'admin.common.delete_complete', and redirects to /admin/customer?customerId=<id> with the address gone. | ⚠️ FLAG (ORPHAN AFFORDANCE): onDelete exists in the resource and ALPS (doDeleteCustomerDeliveryAddress) but is NOT projected to any template — no browser can trigger it. The delete path is testable only via a hand-crafted DELETE request, exactly the SSOT-projection gap class. User-facing delete of a customer address is impossible through the UI. |
| 会員編集ページに戻る link | Click 会員編集ページに戻る. | GET navigation to /admin/customer?customerId=<id> (or /admin/customer-list when customerId empty). | Returns to the member edit page for that customer. | none. |

### admin-content


#### ブロック編集 (Page/Admin/Block/Block) — var/templates/Page/Admin/Block/Block.html.twig

`/admin/block/block?blockId=2` ／ src/Resource/Page/Admin/Block/Block.php　<br>前提: authenticated admin (AdminSession.adminId set). For edit case an existing blockId (e.g. seeded block id=2). Anonymous GET to the new-block form returns 403.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (ブロック編集フォーム submit) | 既存ブロックを開き blockName / blockFileName を変更して登録を押す | POST /admin/block/block?blockId=2&_method=put — csrfToken=<issued>, blockName=<編集値>, blockFileName=<編集値>, block_html=<編集値> (macro fields). blockId は action query string 経由。 | 303 redirect。EC-CUBE faithful では編集画面 admin_content_block_edit?id=2 へ戻り 緑のフラッシュ『保存しました』を表示。 | ⚠️ 二重欠陥。(1) HtmlMutationResponse が常に 303 + Location:/admin/block/block-list を返すため、EC-CUBE の『編集画面へ戻る』ではなく block-list へ飛ぶ（リダイレクト先不一致）。(2) onPut が body['message'] を一切セットせず、かつフラッシュ機構が存在しないため、リダイレクト先 GET は message 無しで再描画 → ユーザーは『保存できた』という可視シグナルを一切得られない。成功の observable が無い＝欠陥。 |
| block_html フィールド | block_html を入力して登録 | block_html=<本文> (macro) | 本文が保存され、再表示時にテキストエリアへ反映される。 | ⚠️ onPut のシグネチャは (blockId, blockName, blockFileName) のみで block_html を受け取らない。UpdateBlockInput にも渡されないため block_html は黙って破棄される。ユーザーが入力した本文が保存されない可能性が高い。 |
| 削除 (doDeleteBlock affordance) | ブロックを削除する（DELETE） | DELETE /admin/block/block?blockId=2 — csrfToken (このテンプレートには削除ボタンが描画されていない: Block.html.twig に btn-ec-delete / _method=delete のリンクが無い) | 対象ブロックが一覧から消え、block-list へ 303 redirect、緑フラッシュ『削除しました』。 | ⚠️ onDelete は実装されているが、テンプレートに削除アフォーダンスが射影されていない（orphan verb）。HTML 画面からは削除を起動できない。さらに onDelete も message をフラッシュに残せないため、起動できたとしても削除成功の可視シグナルは出ない。 |

#### キャッシュ管理 (Page/Admin/Content/Cache) — var/templates/Page/Admin/Content/Cache.html.twig

`/admin/content/cache` ／ src/Resource/Page/Admin/Content/Cache.php　<br>前提: authenticated admin。匿名の GET は 403『この操作には管理者ログインが必要です。』。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| キャッシュ削除ボタン (submit) | キャッシュ削除を押す | POST /admin/content/cache?_method=put — csrfToken=<issued>, mode=content_operation_form (hidden) | 303 redirect /admin/content/cache。EC-CUBE faithful では緑フラッシュ『削除しました』(admin.common.delete_complete) を表示。 | ⚠️ mode=content_operation_form のため onPut は SEE_OTHER(303)+Location を返す（リダイレクト自体は正しい）。しかし body['message']『キャッシュを削除しました。』はリダイレクトで失われ、着地 GET は csrfToken のみで message を描画しない。Cache.html.twig にもメッセージ表示領域が無い → 押しても画面が同じで成否不明。成功の observable が無い＝欠陥。 |

#### カスタマイズCSS編集 (Page/Admin/Content/Css) — var/templates/Page/Admin/Content/Css.html.twig

`/admin/content/css` ／ src/Resource/Page/Admin/Content/Css.php　<br>前提: authenticated admin。匿名 GET は 403。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (id=save-button, submit) | CSS を編集して登録を押す | POST /admin/content/css?_method=put — csrfToken=<issued>, mode=content_operation_form (hidden), css=<編集値> (macro / hidden textarea form.input('css')) | 303 redirect /admin/content/css。EC-CUBE faithful では緑フラッシュ『保存しました』(admin.common.save_complete)。 | ⚠️ (1) 成功メッセージ消失: onPut の body['message']『CSSを更新しました。』はリダイレクトで失われ、着地 GET は message を描画しない（テンプレートに成功領域なし）→ 成否不明。(2) no-JS 欠陥: 入力用 textarea は <div style="display:none"> 内に隠され、可視の編集領域は ACE <div id="editor"> で JS が textarea へ同期する設計。JS 無効だと css の編集値が textarea に乗らず、空文字 css='' が送信され、編集内容が失われる。 |

#### ファイル管理 (Page/Admin/Content/FileManager) — var/templates/Page/Admin/Content/FileManager.html.twig

`/admin/content/file` ／ src/Resource/Page/Admin/Content/FileManager.php　<br>前提: authenticated admin。匿名 GET は 403。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| アップロードボタン (<a class="action-upload" href="javascript:;">) | ファイルを選んでアップロードを押す | form1 (action="?" enctype=multipart/form-data): mode=(空), now_file/now_dir/tree_select_file={{tplNowDir\|default('')}}=(空), tree_status=(空), select_file=(空), csrfToken=<issued>, file=<選択ファイル> (input type=file), create_file=(macro) | 選択ファイルが user_data 配下にアップロードされ、ファイル一覧に新ファイル行が現れる（EC-CUBE faithful: アップロード後に再描画＋一覧更新）。 | ⚠️ 完全な dead form。(1) アップロードボタンは type=submit ではなく <a href="javascript:;"> の JS-only アンカーで、no-JS では何も起きない。(2) form の action="?"（実ターゲット無し）。(3) FileManager リソースは onGet のみで onPost が存在せず、アップロード先エンドポイントが無い。(4) arrFileList は常に空配列を返す renderer のため、仮にアップロードできても一覧に反映されない。ファイル管理機能は機能しない。 |
| フォルダ作成 (create_file 入力 + 作成ボタン) | 新規フォルダ名を入れて作成を押す | create_file=<フォルダ名> (macro), 同上の hidden 群, csrfToken | 新フォルダが作成され、ツリー/一覧に現れる。 | ⚠️ 上と同じ dead form（JS-only アンカー + onPost 無し + action="?"）。フォルダ作成は実行されない。 |

#### カスタマイズJavaScript編集 (Page/Admin/Content/Js) — var/templates/Page/Admin/Content/Js.html.twig

`/admin/content/js` ／ src/Resource/Page/Admin/Content/Js.php　<br>前提: authenticated admin。匿名 GET は 403。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (id=save-button, submit) | JS を編集して登録を押す | POST /admin/content/js?_method=put — csrfToken=<issued>, mode=content_operation_form (hidden), js=<編集値> (macro / hidden textarea form.input('js')) | 303 redirect /admin/content/js。EC-CUBE faithful では緑フラッシュ『保存しました』。 | ⚠️ (1) 成功メッセージ消失: body['message']『JavaScriptを更新しました。』はリダイレクトで失われ着地 GET で描画されない → 成否不明。(2) no-JS 欠陥: 編集用 textarea が display:none、可視の ACE editor が JS で textarea へ同期。JS 無効だと js='' が送られ編集内容が失われる。 |

#### メンテナンス管理 (Page/Admin/Content/Maintenance) — var/templates/Page/Admin/Content/Maintenance.html.twig

`/admin/content/maintenance` ／ src/Resource/Page/Admin/Content/Maintenance.php　<br>前提: authenticated admin。匿名 GET は 403。fresh-install では isMaintenance=false。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 有効にするボタン (isMaintenance=false 時に描画される submit) | メンテナンスモードを有効にする | POST /admin/content/maintenance?_method=put — csrfToken=<issued>, mode=content_operation_form (hidden), enabled=1 (hidden, false 状態のとき value=1) | 303 redirect /admin/content/maintenance。再描画で isMaintenance=true になりボタンが『無効にする』へ切替。EC-CUBE faithful では緑フラッシュ『メンテナンスモードに切り替えました』。 | ⚠️ リダイレクト先で MaintenanceModeInterface.isEnabled() が true を返せば、ボタンが『無効にする』へ変わる＝observable な状態反映があるため切替自体は確認可能。ただし body['message']『メンテナンスモードを有効にしました。』はリダイレクトで失われ、テンプレートにメッセージ表示領域も無いためフラッシュは出ない（軽欠陥）。注: 抽出インベントリは enabled=0 と button『無効にする』を挙げているが、fresh-install(false) では実際に描画されるのは enabled=1 / 『有効にする』のみ（分岐の片側）。 |
| 無効にするボタン (isMaintenance=true 時に描画される submit) | メンテナンスモードを無効にする | POST /admin/content/maintenance?_method=put — csrfToken, mode=content_operation_form, enabled=0 (hidden, true 状態のとき value=0) | 303 redirect、再描画で isMaintenance=false になりボタンが『有効にする』へ切替。 | ⚠️ enabled は onPut で bool 型受け取り。enabled=0/1 文字列が bool へ正しくキャストされ ToggleMaintenanceInput に渡る前提。状態反映(ボタン切替)が observable なので機能確認は可能だが、成功フラッシュは出ない（軽欠陥）。 |

#### レイアウト編集 (Page/Admin/Layout/Layout) — var/templates/Page/Admin/Layout/Layout.html.twig

`/admin/layout/layout?layoutId=2` ／ src/Resource/Page/Admin/Layout/Layout.php　<br>前提: authenticated admin。新規(layoutId 無し) GET で匿名は UnauthorizedAdminAccessException。編集には既存 layoutId が必要。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (レイアウト名保存 submit) | レイアウト名(name)を変更して登録を押す | POST /admin/layout/layout?layoutId=2&_method=put — csrfToken=<issued>, name=<編集値> (macro)。layoutId は action query string。 | 303 redirect。EC-CUBE faithful では編集画面 admin_content_layout_edit?id=2 へ戻り緑フラッシュ『保存しました』。 | ⚠️ (1) リダイレクト先不一致: HtmlMutationResponse が常に 303 + Location:/admin/layout/layout-list を返すため、編集画面ではなく layout-list へ飛ぶ。(2) 成功フラッシュ消失: onPut は message を一切セットせず、フラッシュ機構も無い → 着地で成否シグナル無し。onPut は name→layoutName のフォールバックを実装済みなので名前保存自体は通る想定だが可視確認できない。 |
| ブロック移動 / コードを編集 ボタン (#move-to-section, #block-edit, type=button) | ブロック配置デザイナーでブロックを移動/コード編集する | (送信なし) これらは type=button + data-bs-dismiss でモーダルを閉じるだけ。バックエンドへ送る form 無し。 | EC-CUBE faithful ではブロック配置を変更し保存できる（layout の block 配置永続化）。 | ⚠️ ブロック配置デザイナーは JS-only の placeholder（resource の port header に『block-position designer remains a residual placeholder』と明記）。移動/コード編集は永続化されない。レイアウト機能の中核(ブロック配置)が未実装。 |

#### 新着情報編集 (Page/Admin/News/News) — var/templates/Page/Admin/News/News.html.twig

`/admin/news/news?newsId=1` ／ src/Resource/Page/Admin/News/News.php　<br>前提: authenticated admin。新規フォーム匿名 GET は 403。編集には既存 newsId。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (新着情報保存 submit) | 新着情報のタイトル/本文/公開日/URL を編集して登録を押す | POST /admin/news/news?newsId=1&_method=put — csrfToken=<issued>, linkMethod=0 (hidden) + linkMethod=1 (checkbox, チェック時のみ), publishDate=<値> (macro), newsTitle=<値> (macro), newsUrl=<値> (macro), newsDescription=<値> (macro)。newsId は action query。 | 303 redirect /admin/news/news?newsId=1（編集画面へ戻る — BeMart の Location は EC-CUBE と一致）。緑フラッシュ『保存しました』。 | ⚠️ リダイレクト先は onPut が自前で sprintf('/admin/news/news?newsId=%s', ...) を組み /admin/news/news?newsId=1 に戻すため EC-CUBE と一致（OK）。ただし body['message'] は無く、リダイレクト後の GET は message を描画しないため成功フラッシュが出ない → 成否シグナル無し（欠陥）。テンプレートの {% if message %} は ec-errorMessage(赤・エラー用) で、成功表示には使われない。 |
| linkMethod チェックボックス (別ウィンドウで開く) | 『別ウィンドウで開く』にチェックを入れて登録 | linkMethod=0 (hidden 既定) と linkMethod=1 (checkbox) の両方。チェック時はブラウザが後勝ちで 1 を送る（hidden+checkbox の標準パターン）。 | linkMethod=true として保存され、再表示時にチェックが入った状態で復元される。 | none（hidden 0 + checkbox 1 の二重 input は EC-CUBE 同等の bool 表現。onPut は bool\|null linkMethod を受け取り UpdateNewsInput に渡す。再描画時 {% if linkMethod %} checked で復元）。 |
| 削除 (doDeleteNews affordance) | 新着情報を削除する | DELETE /admin/news/news?newsId=1 — csrfToken（このテンプレートに削除ボタン/リンクは描画されていない） | 対象行が news-list から消え、303 redirect /admin/news/news-list、緑フラッシュ『削除しました』。 | ⚠️ onDelete は実装済みだが News.html.twig に削除アフォーダンス(btn-ec-delete / _method=delete)が射影されていない（orphan verb）。HTML から削除を起動できない。 |

#### ページ編集 (Page/Admin/Page/Page) — var/templates/Page/Admin/Page/Page.html.twig

`/admin/page/page?pageId=1` ／ src/Resource/Page/Admin/Page/Page.php　<br>前提: authenticated admin。新規フォーム匿名 GET は 403。編集には既存 pageId。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (ページ保存 submit) | ページ名/URL/ファイル名/メタ情報を編集して登録を押す | POST /admin/page/page?pageId=1&_method=put — csrfToken=<issued>, pageName=<値>, pageUrl=<値>, pageFileName=<値>, tpl_data=<値>, author=<値>, description=<値>, keyword=<値>, meta_robots=<値>, meta_tags=<値> (すべて macro)。pageId は action query。 | 303 redirect /admin/page/page?pageId=1（編集画面へ戻る — Location は EC-CUBE と一致）。緑フラッシュ『保存しました』。 | ⚠️ (1) 成功フラッシュ消失: body['message'] が無く、リダイレクト後 GET も message を描画しない → 成否不明。(2) フィールド破棄: onPut のシグネチャは (pageId, pageName, pageUrl, pageFileName) のみ。tpl_data(本文), author, description, keyword, meta_robots, meta_tags はリソースで受け取られず UpdatePageInput に渡らないため黙って破棄される。ページ本文やメタ情報を編集しても保存されない。 |
| 削除 (doDeletePage affordance) | ページを削除する | DELETE /admin/page/page?pageId=1 — csrfToken（テンプレートに削除リンク無し） | 対象行が page-list から消え、303 redirect /admin/page/page-list、緑フラッシュ『削除しました』。 | ⚠️ onDelete は実装済みだが Page.html.twig に削除アフォーダンスが射影されていない（orphan verb）。HTML から削除を起動できない。 |

#### テンプレート登録 (Page/Admin/Template/TemplateAdd) — var/templates/Page/Admin/Template/TemplateAdd.html.twig

`/admin/template/template-add` ／ src/Resource/Page/Admin/Template/TemplateAdd.php　<br>前提: authenticated admin。匿名 GET は 403。zip テンプレートファイルを用意。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (テンプレートアップロード submit) | テンプレートコード・名称を入力し zip を選んで登録 | POST /admin/template/template-add (multipart/form-data) — csrfToken=<issued>, templateCode=<値> (macro), templateName=<値> (macro), file=<zipファイル> (macro / input type=file) | 303 redirect /admin/template/template-list、一覧に新テンプレート行が現れ、緑フラッシュ『アップロードしました』(admin.common.upload_complete)。 | ⚠️ 成功フラッシュ消失: onPost 成功時 HtmlMutationResponse が 303+Location:/admin/template/template-list を返すが body['message']『テンプレートを追加しました。』はリダイレクトで失われ、着地 GET で描画されない → 成否シグナル無し。さらに list 側は fake storage で新規行が反映されない可能性（TemplateAdd の port header に『doTemplateInstall は Phase-A stub』『empty JSON-backed fake storage』と明記）→ 一覧に新行が出ない懸念。 |
| 登録ボタン (ファイル未選択時のバリデーション) | file を空のまま登録を押す | templateCode=<値>, templateName=<値>, file=(なし) | field-level バリデーションエラー『テンプレートファイルを選択してください。』が画面に表示される。 | ⚠️ onPost は file===null で Code::BAD_REQUEST + body['message'] を返すが、これは GET 再描画ではなく 400 レスポンス本文。テンプレートの {% if form.error('file') %} 領域には載らず（form.error はドメインエラー用）、body['message'] を描画する領域も TemplateAdd.html.twig に無いため、ユーザーには素の 400 ページが見える可能性。エラーの可視性が EC-CUBE(inline field error)と不一致。 |

#### テンプレート一覧 (Page/Admin/Template/TemplateList) — var/templates/Page/Admin/Template/TemplateList.html.twig

`/admin/template/template-list` ／ src/Resource/Page/Admin/Template/TemplateList.php　<br>前提: authenticated admin。一覧に既存テンプレート(radio 選択肢)が存在すること。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (テンプレート適用 / doSelectTemplate submit) | ラジオでテンプレートを選び登録を押す | POST /admin/template/template-list?_method=put — csrfToken=<issued>, templateId=(空, hidden #form_selected), template=<選択 radio value>。submit 時に JS が $('#form_selected').val(チェック済 radio) を埋める。 | 選択テンプレートが適用され 303 redirect /admin/template/template-list、再描画で当該行が active(checked) 表示、緑フラッシュ『保存しました』。 | ⚠️ (1) JS-only 欠陥: onPut が受け取るのは hidden templateId のみ。templateId は既定 value="" で、submit ハンドラ($('#form_selected').val(...))でしか埋まらない。JS 無効だと templateId='' が送られ、param JsonSchema(string 必須)違反で 400、または空 ID で適用されない。template ラジオの値はリソースに渡らない。(2) 成功フラッシュ消失: body['message']『テンプレートを適用しました。』はリダイレクトで失われ着地で描画されない → 成否不明。 |
| 削除リンク (btn-ec-delete / doDeleteTemplate) | テンプレートの削除をモーダルで確認し『削除』を押す | GET(method-override) /admin/template/template-list?templateId={{Template.templateId}}&_method=delete — <a class="btn btn-ec-delete" data-confirm="false"> アンカー。templateId は href に埋め込み済み。 | 対象行が一覧から消え、303 redirect /admin/template/template-list、緑フラッシュ『削除しました』。デフォルト/適用中テンプレートは EC-CUBE では削除エラー『削除できません』。 | ⚠️ (1) 成功フラッシュ消失: onDelete の message が着地 GET で描画されない → 削除の成否はリスト再描画(行消失)でのみ判断可、フラッシュは出ない。(2) EC-CUBE のデフォルト/適用中テンプレート削除ガード(addError)が BeMart 側に無く、無条件削除されると一覧再描画でしか分からない。href に templateId 埋め込み済みなので削除起動自体は no-JS でも動く（data-confirm=false で確認モーダルもスキップ可）。 |
| ダウンロード (doDownloadTemplate / POST) | テンプレートのダウンロードアイコンを押す | POST /admin/template/template-list — templateId=<対象>（テンプレートのダウンロードリンクは title="ダウンロード" のアイコン; 実際の送信形態を要確認） | application/zip の添付ファイルがダウンロードされる（Content-Disposition: attachment）。 | ⚠️ onPost は templateId 必須で zip を返す。ダウンロードトリガが <a> リンク(GET)なのか POST フォームなのか要確認 — POST 必須なのにアンカー(GET)だと到達しない可能性。templateId が空だと 400。 |

### admin-shop


#### 基本情報設定 (Page/Admin/BaseInfo) — var/templates/Page/Admin/BaseInfo.html.twig

`/admin/base-info` ／ src/Resource/Page/Admin/BaseInfo.php　<br>前提: authenticated admin. dtb_base_info single row must be seeded (seed-dev.sh). Anonymous → onGet/onPost have no AdminSession guard here, so AUTHZ is enforced only via the Be chain / UnauthorizedAdminAccessException.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (shop_master_form の submit) | GET /admin/base-info で表示されるフォームをそのまま（既存の dtb_base_info 値で）登録ボタン押下。 | POST /admin/base-info: company_name=(現値), shop_name=(現値 例 'EC-CUBE SHOP'), shop_kana=(現値), shop_name_eng=(現値), postal_code=(現値), pref=(現値 数値 or 空), addr01=(現値), addr02=(現値), phone_number=(現値), business_hour=(現値), email01=(現値), shop_message=(現値), csrfToken=(発行値)。注意: フォームの name は email01/pref だが onPost 引数は shopEmail01/pref へマップされる。 | EC-CUBE 忠実: 302 redirect → /admin/setting/shop (BeMart では /admin/base-info) + 緑のフラッシュ '保存しました' が再表示画面に出る。 | ⚠️ FLAG: onPost は MutationResponse を使わず Code::OK を直接返し redirect も Location も無い → ブラウザは POST 応答をその場で描画し、正規の GET 画面へ遷移しない。さらに admin-base から @admin/alert.twig フラッシュ include が落ちているため '保存しました' バナーは一切出ない → ユーザーは保存できたか判別不能（まさに当該バグパターン）。changed フラグは body にあるが画面に出ない。 |
| 登録ボタン (shop_name を空にして送信) | ショップ名 (shop_name) を空文字にして登録。 | POST /admin/base-info: shop_name=(空), 他=(現値), csrfToken=(発行値)。 | EC-CUBE 忠実: フォーム再表示 + shop_name 直下にフィールドエラー 'ショップ名を入力してください' が赤字表示。 | ⚠️ FLAG: param schema は minLength:0 を許容するため 400 にならず Be 層へ通る。onPost の $shopName は string 必須（null 不可）なので空文字は通過し、Semantic 制約で SemanticVariableException → 400 エラーページになる可能性。テンプレートには form.error('shop_name') 行はあるが、400 で別ページに飛ぶとこのエラー欄に戻らず、ユーザーは EC-CUBE のような inline エラーを見られない恐れ。要実機確認。 |

#### 定休日カレンダー設定 (Page/Admin/Calendar) — var/templates/Page/Admin/Calendar.html.twig

`/admin/calendar` ／ src/Resource/Page/Admin/Calendar.php　<br>前提: authenticated admin (onGet/onPost/onDelete とも adminSession.adminId==null なら 403 '管理者ログインが必要です')。dtb_calendar に既存休日が無くても新規作成は可能。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 新規作成ボタン (form1 / doCreateCalendarHoliday) | タイトルと休日日付を入力し新規作成を押下。 | POST /admin/calendar?operation=create: title=(入力値 例 '年末年始'), holiday=(入力値 例 '2026-12-31'), csrfToken=(発行値)。operation は query で create。 | EC-CUBE 忠実: 302 redirect → /admin/calendar + フラッシュ '保存しました'、一覧に新しい休日行が出現する。 | ⚠️ redirect は出る (HtmlMutationResponse が Code::SEE_OTHER + Location:/admin/calendar を強制、CREATED は無視)。ただしフラッシュ '保存しました' は admin-base にフラッシュ領域が無いため表示されない → 成功の唯一の観測点は再取得した一覧に行が増えること。title/holiday が空だと Be 層で SemanticVariableException → 400 の懸念（form.error('title')/('holiday') 行はあるが 400 別ページに飛ぶと見えない）。 |
| 決定ボタン (edit-form / doUpdateCalendar、行インライン編集) | 既存休日行の編集を開き title/holiday を変更して決定。 | POST /admin/calendar?operation=update: calendarId={{Calendar.id}} (hidden), mode=edit_inline (hidden), title=(変更値), holiday=(変更値), csrfToken=(発行値)。 | EC-CUBE 忠実: 302 redirect → /admin/calendar + '保存しました'、一覧の該当行が更新後の値で再描画。 | ⚠️ redirect は出るが '保存しました' フラッシュ無し。注意: onPost は operation!=create の時 UpdateCalendarHolidayInput に calendarId を渡すが、フォームは calendarId を送るものの mode=edit_inline は onPost 引数に無く無視される（害は無い）。インライン編集 UI は JS で .edit を表示する作りなので、JS 無効だと決定ボタンに到達できない（行は .list 表示のまま）。 |
| 削除リンク (削除モーダル内 <a href=...&_method=delete>) | 行の削除アイコン→モーダル→削除を押下。 | GET ナビゲーション /admin/calendar?calendarId={{Calendar.id}}&_method=delete（data-confirm=false、JS が data-method を見て hidden POST フォーム化して _method=delete を送る）。 | EC-CUBE 忠実: onDelete 実行 → 302 redirect → /admin/calendar + '削除しました'、一覧から該当行が消える。 | ⚠️ FLAG (JS依存): 削除は <a> リンク。public/assets/js/function.js が data-method を読んで POST(_method=delete) フォームを生成する仕組みのため、JS 無効だと素の GET 遷移になり onDelete に届かず削除されない（405 もしくは onGet 描画）。成功時も '削除しました' フラッシュは表示されないので、観測点は『行が一覧から消える』のみ。 |

#### CSV出力項目設定 (Page/Admin/CsvConfig) — var/templates/Page/Admin/CsvConfig.html.twig

`/admin/csv-config` ／ src/Resource/Page/Admin/CsvConfig.php　<br>前提: authenticated admin (onGet は adminSession.adminId==null で 403)。csvType は GET 既定 3(受注)。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (csv-form / doUpdateCsv) | 出力項目を左右に移動して並べ替え、登録を押下。 | POST /admin/csv-config: csvType=(現在の type 例 3), csvOutput[]=(出力に並んだ columnName 群), csvNotOutput[]=(非出力の columnName 群), csrfToken=(発行値)。csvOutput/csvNotOutput が空配列でも送られ得る。 | EC-CUBE 忠実: 302 redirect → /admin/csv-config?id={csvType} (BeMart は ?csvType={csvType}) + '保存しました'、選んだ列構成が再描画で保持される。 | ⚠️ redirect は出る (HtmlMutationResponse、Location:/admin/csv-config?csvType=N)。'保存しました' フラッシュ無し。csvType がフォームから送られない/不正だと onPost の $csvType (int 必須) で 400。Wave 9 注記どおり、保存しても実際の CSV エクスポート列はハードコードのままなので、設定が CSV 出力に反映されることは確認できない（永続化のみ）。 |
| ↑↓ / 最上位移動ボタン (.move / .move-most) | 項目の並び順を移動ボタンで変更。 | クライアント内 DOM 操作のみ（submit 無し）。実際の保存は上記『登録』で送信される csvOutput/csvNotOutput の順序に反映。 | 並べ替え操作で即座に画面上の項目順が変わる（その後 登録 で保存）。 | ⚠️ FLAG (JS依存): 並べ替えは純粋に JS。JS 無効だと順序変更不可。これ自体は EC-CUBE も同様だが、no-JS では登録時に既定順しか送られない点に注意。 |

#### 配送方法設定 編集/新規 (Page/Admin/Delivery/Delivery) — var/templates/Page/Admin/Delivery/Delivery.html.twig

`/admin/delivery/delivery (新規) / /admin/delivery/delivery?deliveryId=N (編集)` ／ src/Resource/Page/Admin/Delivery/Delivery.php　<br>前提: authenticated admin。編集は dtb_delivery に対象 deliveryId が存在すること（不在 → onGet 404 '指定された配送方法は見つかりませんでした'）。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (新規モード, editing=false) | /admin/delivery/delivery を開き（deliveryId 空）配送業者名を入力して登録。 | POST /admin/delivery/delivery-list (新規時 action は delivery-list): deliveryName=(入力値), visible=(チェックボックス '1' or 未送信), deliveryId=(空), csrfToken=(発行値)。新規時は _method=put hidden は出力されない（{% if editing %} ガード）。 | EC-CUBE 忠実: DeliveryList.onPost → 302 redirect → /admin/delivery/delivery?deliveryId={新ID} + '保存しました'、その編集画面に新規作成した配送方法が表示。 | ⚠️ redirect は出る (HtmlMutationResponse、Location は新規 deliveryId 付き)。'保存しました' フラッシュ無し。deliveryName 空なら DeliveryList.onPost の必須引数($deliveryName)に空が通り Semantic 例外 → 400 の懸念（form.error('deliveryName') はあるが 400 別ページだと見えない）。 |
| 登録ボタン (編集モード, editing=true) | /admin/delivery/delivery?deliveryId=N を開き配送業者名/有効無効を変更して登録。 | POST /admin/delivery/delivery?deliveryId=N&_method=put: deliveryId=N (hidden), _method=put (hidden), deliveryName=(変更値), visible=('1' or 未送信), csrfToken=(発行値)。 | EC-CUBE 忠実: onPut → 302 redirect → /admin/delivery/delivery?deliveryId=N + '保存しました'、変更後の値が編集画面に再表示。 | ⚠️ redirect は出る。'保存しました' フラッシュ無し → 観測点は再描画された値が変更後になっていること。visible チェックボックス未チェック時は visible 未送信→onPut の bool\|null $visible=null（=変更なし扱い）になり、EC-CUBE の『無効化』意図とずれる可能性あり（チェックを外しても無効化されない恐れ）。要実機確認。 |

#### 配送方法一覧 (Page/Admin/Delivery/DeliveryList) — var/templates/Page/Admin/Delivery/DeliveryList.html.twig

`/admin/delivery/delivery-list` ／ src/Resource/Page/Admin/Delivery/DeliveryList.php　<br>前提: authenticated admin。dtb_delivery に1件以上あると行操作が見える。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 新規作成リンク (<a href=/admin/delivery/delivery>) | 新規作成ボタン押下。 | GET /admin/delivery/delivery（フォーム送信ではなく単純リンク）。 | 配送方法の新規編集フォーム画面 (Delivery 編集, 空フォーム) が表示される。 | none（単純な GET リンク、Delivery.onGet が deliveryId 空で空フォームを返す）。 |
| 削除リンク (削除モーダルの <a data-post-action=delete>) | 行の削除アイコン→モーダル→削除を押下。 | JS が data-url=/admin/delivery/delivery?deliveryId=N&_method=delete を hidden POST フォーム(_method=delete)に変換して送信。 | EC-CUBE 忠実: Delivery.onDelete → 302 redirect → /admin/delivery/delivery-list + '削除しました'、一覧から該当行が消える。 | ⚠️ FLAG (JS依存・no-JSで完全に不動): モーダル確定リンクは href='#' で、実 URL は shown.bs.modal の JS ハンドラが data-url から注入する。JS 無効だと href='#' のまま=何も起きない。さらに削除送信も function.js の data-method→POST 変換に依存。成功しても '削除しました' フラッシュ無し → 観測点は『行が消える』のみ。 |
| 有効/無効トグルリンク (.action-visible) | トグルアイコン押下。 | GET 風 <a href=/admin/toggle-visible?masterType=delivery&rowId=N&_method=put>（JS で POST 変換）。送信先は別リソース /admin/toggle-visible。 | アイコンが on/off 反転し、一覧再描画で表示状態が変わる。 | ⚠️ FLAG (JS依存 + 別リソース): /admin/toggle-visible は本グループ外。JS 無効だと素の GET になり機能しない。toggle-visible リソースの実装有無は別途確認が必要（未実装なら 404/405）。 |
| 並び替え (ドラッグ&ドロップ / ↑↓) | 行をドラッグ、または ↑↓ ボタンで並べ替え。 | JS Ajax POST /admin/sort-no-move?masterType=delivery&_method=put に {deliveryId: sortNo,...}。 | 並べ替え後、表示順が即時反映され永続化される。 | ⚠️ FLAG (JS依存 + 別リソース + 欠落データ): data-sort-no が空でレンダリングされる（DeliveryList 投影が sortNo を持たない、テンプレ docblock が明記）→ JS の oldSortNos が空配列になり並べ替えが正しく送れない可能性大。送信先 /admin/sort-no-move も本グループ外で実装確認要。実質ほぼ機能しない見込み。 |

#### メールテンプレート設定 (Page/Admin/MailTemplate) — var/templates/Page/Admin/MailTemplate.html.twig

`/admin/mail-template` ／ src/Resource/Page/Admin/MailTemplate.php　<br>前提: authenticated admin (onGet/onDelete は adminSession.adminId==null で 403)。dtb_mail_template にテンプレートが seed 済みであること。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (form1 / doUpdateMailTemplate) | テンプレートを選び件名(mail_subject)を変更して登録。 | POST /admin/mail-template: mailTemplateId=(選択ID), template=(選択ID), name=(テンプレ名), file_name=(ファイル名), mail_subject=(変更件名), tpl_data=(本文), html_tpl_data=(HTML本文), csrfToken=(発行値)。注意: onPost が読むのは mailTemplateId と mailSubject/mail_subject のみ。tpl_data/html_tpl_data は受け取られず破棄される。 | EC-CUBE 忠実: 302 redirect → /admin/mail-template?id={mailTemplateId} (BeMart は ?mailTemplateId=) + '保存しました'、変更後の件名が再表示。 | ⚠️ FLAG (機能欠落): redirect は出る (HtmlMutationResponse) が '保存しました' フラッシュ無し。本文 (tpl_data/html_tpl_data) はフォームにあるが onPost が無視するため、本文を編集して登録しても保存されない（件名のみ反映）→ ユーザーは本文が保存されたと誤認。docblock も件名のみ対応と明記。mailTemplateId が送られない/非整数だと onPost int 必須引数で 400。 |
| 作成ボタン (form-create / doCreateMailTemplate) | 新規テンプレート名・ファイル名・件名を入力して作成。 | POST /admin/mail-template/create: mailTemplateName=(入力), fileName=(入力), mailSubject=(入力)。送信先は別リソース Page/Admin/MailTemplate/Create（本グループ外）。 | EC-CUBE 忠実: 新規テンプレート作成 → 302 redirect + '保存しました'、一覧に新テンプレートが出現。 | ⚠️ FLAG (別リソース・スコープ外): 送信先は /admin/mail-template/create = Page/Admin/MailTemplate/Create で本リソースには無い。MailTemplate docblock は『新規作成は Phase 2 スコープ』と明記しており、Create リソースが未実装/スタブだと作成は失敗する。Create リソースの実機確認が必要。 |
| 削除リンク (deleteModal 内 <a rel=doDeleteMailTemplate>) | 削除ボタン→モーダル→削除を押下。 | GET 風 <a href=/admin/mail-template?mailTemplateId={{Mail.id}}&_method=delete>（JS で POST(_method=delete) 変換）。 | EC-CUBE 忠実: onDelete → 302 redirect → /admin/mail-template + '削除しました'、一覧から該当テンプレートが消える。 | ⚠️ FLAG (JS依存 + 暫定実装): onDelete は実在するが body message が '...削除Resourceへ到達しました'（暫定実装）。削除は JS の data-method→POST 変換依存で no-JS だと不動。'削除しました' フラッシュ無し。さらに削除ボタン押下時の Mail.id は onGet で選択テンプレートが無い(mailTemplateId 未指定)と null → href が ...mailTemplateId=&_method=delete となり 400/誤動作の懸念。 |
| プレビューボタン (preview_button) | プレビューを押下。 | submit 無し（data-bs-toggle=modal でモーダル表示のみ）。 | HTMLプレビューモーダルが開く。 | ⚠️ FLAG (JS依存): type=button + Bootstrap modal。JS 無効だと何も起きない。サーバ機能ではないので機能上の保存リスクは無い。 |

#### 支払方法設定 編集/新規 (Page/Admin/Payment/Payment) — var/templates/Page/Admin/Payment/Payment.html.twig

`/admin/payment/payment (新規) / /admin/payment/payment?paymentId=N (編集)` ／ src/Resource/Page/Admin/Payment/Payment.php　<br>前提: authenticated admin。編集は dtb_payment に対象 paymentId が存在すること（不在 → onGet 404 '指定された支払方法は見つかりませんでした'）。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (編集モード, doUpdatePayment) | /admin/payment/payment?paymentId=N を開き、支払方法名/手数料/利用条件を変更して登録。 | POST /admin/payment/payment?paymentId=N&_method=put: paymentId=N (hidden), _method=put (hidden), paymentMethodName=(変更値), charge=(数値), ruleMin=(数値), ruleMax=(数値), visible=('1' or 未送信), csrfToken=(発行値)。 | EC-CUBE 忠実: onPut → 302 redirect → /admin/payment/payment?paymentId=N + '保存しました'、変更後の値が編集画面に再表示。 | ⚠️ redirect は出る (HtmlMutationResponse、Location は paymentId 付き)。'保存しました' フラッシュ無し → 観測点は再描画値。charge/ruleMin/ruleMax が空文字だと onPut の int\|null へのキャストで 0/型エラーの懸念。visible 未チェック時は未送信→null（変更なし扱い）で『無効化』意図とずれる恐れ。要実機確認。 |
| 登録ボタン (新規モード, editing=false) | /admin/payment/payment を開き（paymentId 空）新規支払方法を入力して登録。 | POST /admin/payment/payment-list（新規時 action）: paymentMethodName 等。送信先は別リソース Page/Admin/Payment/PaymentList（本グループ外）。新規時 _method=put hidden は出力されない。 | EC-CUBE 忠実: PaymentList.onPost(create) → 302 redirect → /admin/payment/payment?paymentId={新ID} + '保存しました'。 | ⚠️ FLAG (別リソース・スコープ外): 新規作成の送信先 /admin/payment/payment-list は本グループ外の PaymentList。その doCreate 実装の有無は別途確認が必要（未実装なら新規作成不可）。 |
| 削除リンク (本画面には削除モーダルが見当たらない) | 支払方法の削除（一覧 PaymentList 側で実行される想定）。 | onDelete: DELETE /admin/payment/payment?paymentId=N（PaymentList 一覧の削除リンク経由、本テンプレートには無い）。 | EC-CUBE 忠実: onDelete → 302 redirect → /admin/payment/payment-list + '削除しました'、一覧から消える。 | ⚠️ FLAG (アフォーダンス所在の確認要): Payment リソースに onDelete は実装済みだが、この編集テンプレートには削除 UI が無い。削除起動は PaymentList(スコープ外)側の JS リンクに依存するはずで、その投影漏れがあると削除不能。PaymentList テンプレの削除リンク存在を別途確認。 |

#### 税率設定 (Page/Admin/TaxRule/TaxRuleList) — var/templates/Page/Admin/TaxRule/TaxRuleList.html.twig

`/admin/tax-rule/tax-rule-list` ／ src/Resource/Page/Admin/TaxRule/TaxRuleList.php　<br>前提: authenticated admin。dtb_tax_rule の既定税率が seed 済み。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 新規作成ボタン (form1 / doCreateTaxRule) | 税率と適用日時を入力して新規作成。 | POST /admin/tax-rule/tax-rule-list: taxRate=(入力 例 10), applyDate=(datetime-local 例 '2026-04-01T00:00'), csrfToken=(発行値)。 | EC-CUBE 忠実: onPost → 302 redirect → /admin/tax-rule/tax-rule-list (BeMart の Location は /admin/tax-rule/tax-rule?taxRuleId=新ID) + '保存しました'、一覧に新税率行が出現。 | ⚠️ FLAG (redirect先ずれ): HtmlMutationResponse で 303 redirect は出るが Location は /admin/tax-rule/tax-rule?taxRuleId=N（単票リソース、本グループ外）を指す。そのリソースが未実装/スタブだと redirect 先で 404/エラー。'保存しました' フラッシュ無し。taxRate 空/applyDate 空だと onPost の float/string 必須引数で 400 もしくは Semantic 例外、テンプレ docblock 明記どおり has-error 表示は無いのでユーザーにエラーが見えない。 |
| 決定ボタン (edit-form / インライン編集) | 既存行の編集アイコン→インライン編集→決定を押下。 | POST /admin/tax-rule/tax-rule-list: tax_rule_id={{TaxRule.taxRuleId}} (hidden), mode=edit_inline (hidden)。taxRate/applyDate の入力欄はこの編集行に存在しない。 | EC-CUBE 忠実: 該当税率の更新 → 302 redirect + '保存しました'、行が更新値で再描画。 | ⚠️ FLAG (壊れたアフォーダンス/孤立): ALPS に doUpdateTaxRule が無く、onPost は taxRate(float) と applyDate(string) を必須要求するが、この編集フォームは tax_rule_id と mode のみ送信し taxRate/applyDate を送らない → 必須パラメータ欠落で 400/エラー。テンプレ docblock も『edit パネルは BeMart 送信先を持たない・残渣』と明記。実機では決定押下で確実に失敗する。 |
| 削除リンク (削除モーダル内 <a href=...&_method=delete>) | 行の削除アイコン→モーダル→削除を押下。 | GET 風 <a href=/admin/tax-rule/tax-rule?taxRuleId={{TaxRule.taxRuleId}}&_method=delete>（JS で POST 変換）。送信先は単票リソース /admin/tax-rule/tax-rule（本グループ外）。 | EC-CUBE 忠実: doDeleteTaxRule → 302 redirect → /admin/tax-rule/tax-rule-list + '削除しました'、一覧から該当行が消える。 | ⚠️ FLAG (JS依存 + 別リソース): 削除先 /admin/tax-rule/tax-rule は本グループ外（TaxRuleList には onDelete 無し、Link rel=doDeleteTaxRule は別パスを指す）。当該リソースの実装有無の確認要。JS 無効だと素の GET 遷移で機能しない。'削除しました' フラッシュ無し。 |

#### 特定商取引法設定 (Page/Admin/TradeLaw) — var/templates/Page/Admin/TradeLaw.html.twig

`/admin/trade-law` ／ src/Resource/Page/Admin/TradeLaw.php　<br>前提: authenticated admin (onGet は adminSession.adminId==null で 403)。dtb_csv... ではなく特商法ボディが seed 済みであること。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 保存ボタン (doUpdateTradeLaw) | 各項目(項目名/説明)を編集して保存。 | POST /admin/trade-law: mode=trade_law_form (hidden), trade_law_1_name..trade_law_6_name=(各値), trade_law_1_description..trade_law_6_description=(各値), trade_law_N_displayOrderScreen=(チェック値), csrfToken=(発行値)。tradeLawBody は通常フォームから送られず、resource が行から組み立てる。 | EC-CUBE 忠実: 302/303 redirect → /admin/trade-law + '保存しました'、編集後の項目が再表示。 | ⚠️ 唯一 redirect が明示実装されている画面: mode='trade_law_form'(フォーム既定)なので onPost は Code::SEE_OTHER + headers['Location']='/admin/trade-law' を返し正規 GET へ 303 遷移する → 再描画で保存値が見える。ただし '保存しました' フラッシュは admin-base にフラッシュ領域が無く表示されない。displayOrderScreen はコード上 unset され完全に無視される（チェックしても保存されない）→ EC-CUBE の『表示する/しない』設定が効かない FLAG。tradeLawBody が長すぎると Semantic 例外で 400 の懸念。 |

### admin-system


#### 管理者権限設定 (AuthorityRole) — Page/Admin/AuthorityRole.html.twig

`/admin/authority-role` ／ src/Resource/Page/Admin/AuthorityRole.php　<br>前提: authenticated admin (adminSession.adminId != null). Anonymous → GET returns Code::FORBIDDEN with message 'この操作には管理者ログインが必要です。'


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (type=submit, ラベル '登録') | URL アクセス制限ルールを1行以上入力して登録ボタンを押す（POST /admin/authority-role） | csrfToken=<token>, AuthorityRoles[0][Authority]=<option.id 例 0>, AuthorityRoles[0][deny_url]=<例 product/> （行ごとに loop.index0 で連番） | EC-CUBE faithful: 303 redirect → /admin/authority-role で再表示され、保存された deny ルール行が一覧に再描画され、成功フラッシュ '権限設定を更新しました。' が表示される。HTML context では HtmlMutationResponse が Code::SEE_OTHER + Location を強制するためリダイレクトは出る。 | ⚠️ テンプレートに成功メッセージ表示領域が無い（grep で message/alert/success 無し）。onPost は body['message']='権限設定を更新しました。' を返すが redirect 後の GET 再描画ページにはこの message が無いため、ユーザーは『更新できた』という明示シグナルを見られない（行が再描画されるだけ）。保存された行が確実に再表示されるか要確認。FLAG: 成功フラッシュが画面に出ない。 |
| 削除ボタン (各行の type=button class=delete) | ルール行の削除ボタンを押してから登録ボタンで送信 | 削除ボタン自体は type=button（JS で行を DOM から除去するだけ）。送信は登録ボタン経由で残った AuthorityRoles[*] のみ POST。 | 削除した行が送信ペイロードから外れ、登録後の再描画で当該 deny ルールが一覧から消える。 | ⚠️ JS-only: 削除ボタンは type=button + jQuery 依存（.delete の click ハンドラ）。JS 無効時は行が消えず削除できない。さらに上記同様、再描画後に成功シグナルが無い。 |

#### ログ表示 (Log) — Page/Admin/Log.html.twig

`/admin/log` ／ src/Resource/Page/Admin/Log.php　<br>前提: authenticated admin。Anonymous → Code::FORBIDDEN 'この操作には管理者ログインが必要です。'


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 読み込むボタン (type=submit, ラベル '読み込む') | ファイル選択 (files select) と表示行数 (line_max) を選んで読み込むを押す（GET /admin/log?files=...&line_max=...） | files=<選択したログファイル名>, line_max=<選択した行数 例 100>（form.input マクロ経由のフォーム既定値） | EC-CUBE faithful: 選択したログファイルの末尾 line_max 行が画面のログ表示領域に再描画される（選択を変えると内容が変わる）。 | ⚠️ FLAG（実害）: Log::onGet() はパラメータを一切受け取らない（シグネチャ public function onGet(): static）。常に固定の 'site.log' を self::LINE_MAX 行で tail する。ユーザーが files / line_max をどう変えても出力は変わらない＝フォームが機能していない。送信した値は無視される。 |

#### 管理者ログイン (Login) — Page/Admin/Login.html.twig

`/admin/login` ／ src/Resource/Page/Admin/Login.php　<br>前提: anonymous（未ログイン）。有効な管理者アカウントが DB に存在すること。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| ログインボタン (type=submit, ラベル 'ログイン') — 正常系 | 正しい loginId / password を入力してログインを押す（POST /admin/login） | mode=login (hidden), loginId=<管理ID>, password=<パスワード>, csrfToken=<token> | 303 SEE_OTHER redirect。2FA 有効アカウント → /admin/two-factor-auth、2FA 未設定 → /admin/two-factor-auth-set。browserForm（mode=login）判定で Code::SEE_OTHER を返す。 | none（onPost は browserForm 時 Code::SEE_OTHER + headers['Location'] をセット。loginChallenge.startVerification/startSetup でセッション状態を作るので後続 2FA 画面が成立する）。 |
| ログインボタン — 認証失敗系 | 誤ったパスワードでログインを押す | mode=login, loginId=<存在する管理ID>, password=<誤り>, csrfToken=<token> | EC-CUBE faithful: ログイン画面に留まり、フィールドエラー（loginId に '管理者IDまたはパスワードが正しくありません' 相当）が表示される。 | none に近い。AdminLoginFailedException を捕捉し rejectForm(..., Code::UNAUTHORIZED) でフォームを再描画し setDomainError('loginId', ...)。ただしテンプレートが form.error('loginId') を描画しているか要確認（描画していなければエラー文言が見えない）。 |
| ログインボタン — 空入力系 | loginId/password を空のままログインを押す | mode=login, loginId=(空), password=(空), csrfToken=<token> | フィールドレベル検証エラー（必須）がフォーム上に表示され、ログイン画面に留まる。 | none に近い。browserForm 時 formErrors() で空チェック→ rejectForm。SemanticVariableException 経路でも browserForm なら rejectForm に変換。非ブラウザ（mode 無し）では例外が再 throw される点に注意。 |

#### ログイン履歴 (LoginHistory) — Page/Admin/LoginHistory.html.twig

`/admin/login-history` ／ src/Resource/Page/Admin/LoginHistory.php　<br>前提: authenticated admin。dtb_login_history に履歴データが存在すること。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 検索ボタン (type=submit, ラベル '検索') | ログインID・IPアドレス欄に値を入れて検索を押す（GET /admin/login-history） | （フォーム inventory の fields は空）テンプレートでは検索入力（form_widget）が明示的に OMIT されているため、ブラウザは実質クエリ無しの GET を送る。csrfToken はGET検索のため通常無し。 | EC-CUBE faithful: 入力したログインID/IP で履歴一覧が絞り込まれ、'検索結果：N件が該当しました' が絞り込み後件数に更新され、一覧が再描画される。 | ⚠️ FLAG（実害）: 検索入力フィールドがテンプレートから OMIT されており（コメント『the omitted inputs are ... restore the inputs』）、onGet(int $limit = 50) は limit しか受け取らない。検索条件で絞り込めない＝何を入力しても常に同じ先頭50件が返り、'検索結果：N件' も固定。検索ボタンが機能していない。 |

#### マスタデータ管理 (MasterData) — Page/Admin/MasterData.html.twig

`/admin/master-data` ／ src/Resource/Page/Admin/MasterData.php　<br>前提: authenticated admin。Anonymous → onGet Code::FORBIDDEN 'この操作には管理者ログインが必要です。'


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 選択ボタン (フォーム1, type=submit, ラベル '選択') | マスタ種別 (masterType select) を選んで選択を押す（POST /admin/master-data?_method=put → onPut） | masterType=<例 tag>（form.input マクロ既定値）, csrfToken=<csrf_token('admin_master_data')> | 選択した masterType の編集行（rows: id/name の表）が下部の編集フォームに再描画され、選択したマスタ名がプリセットされる。 | none に近い。onPut は SelectMasterDataInput→ Code::OK で form/rows/selectedMaster を返し再描画。これは『選択して編集対象をロードする』操作なのでリダイレクト不要、再描画が観測点。masterType 既定値が空にならないか（select の先頭 option value）を要確認（空だと SelectMasterDataInput が落ちうる）。 |
| 保存ボタン (フォーム2, type=submit, ラベル '保存') | 編集表の各行 name を変更して保存を押す（POST /admin/master-data-edit?_method=put → 別リソース MasterDataEdit） | masterType=<selectedMaster> (hidden), rows[0][id]=<data.id>, rows[0][name]=<data.name>（行ごと loop.index0）, csrfToken=<csrf_token('admin_master_data')> | EC-CUBE faithful: 303 redirect で /admin/master-data?... に戻り、編集内容が反映された行が再表示され成功フラッシュ '保存しました' 相当が表示される。 | ⚠️ 保存先 target は別リソース Page/Admin/MasterDataEdit（本グループ外）。MasterDataEdit::onPut の Location/Code/message は別途検証が必要（このグループでは未確認）。rows の id/name 既定値が編集前の値そのままなので、無変更保存でも観測可能な成功シグナルが出るか要確認。 |

#### メンバー登録・編集 (Member) — Page/Admin/Member.html.twig

`/admin/member` ／ src/Resource/Page/Admin/Member.php　<br>前提: authenticated admin。編集時は ?loginId=<既存ID> でアクセスし is_edit=true。Anonymous → onGet Code::FORBIDDEN。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (新規作成 is_edit=false, type=submit, ラベル '登録') | name/loginId/password/passwordConfirm/authority 等を入力して登録（POST /admin/member） | mode=member_form (hidden), name=<名前>, department=<部門>, loginId=<新ID>, password=<PW>, passwordConfirm=<PW>, authority=<権限値>, twoFactorAuthEnabled=<0/1>, csrfToken=<token> | browserForm 時 303 SEE_OTHER → /admin/member?loginId=<urlencode(loginId)>（作成した管理者の編集画面）。EC-CUBE faithful の『登録しました』相当に対応。非ブラウザは Code::CREATED。 | none。onPost は browserForm 時 Code::SEE_OTHER + Location をセット。ただし PRG 後の編集画面に成功フラッシュが描画されるか（body の message 領域）は要確認。 |
| 登録ボタン (編集 is_edit=true, action=/admin/member?_method=put → onPut) | 既存メンバーの name/department/権限を変更して登録（PUT） | mode=member_form, name=<変更後>, department=<...>, loginId=<既存ID>, password=(任意/空可), passwordConfirm=(任意/空可), authority=<...>, twoFactorAuthEnabled=<...>, csrfToken=<token> | 303 SEE_OTHER → /admin/member?loginId=<urlencode(loginId)> で更新後の値が再表示される。 | none。onPut は browserForm 時 Code::SEE_OTHER + Location をセット。 |
| 登録ボタン — バリデーションエラー系 | passwordConfirm を password と不一致、または loginId 重複/不正形式で登録 | 上記に加え password != passwordConfirm、または既存 loginId | フォーム画面に留まり、該当フィールドのエラー（例 'パスワードが一致しません' / 'このIDは既に使われています' 相当）が field-level で表示される。 | none に近い。rejectForm が setDomainError(field, message) でフィールド単位エラーをセットし Code::BAD_REQUEST。テンプレが各 form.error(field) を描画しているか要確認。 |

#### メンバー一覧 (MemberList) — Page/Admin/MemberList.html.twig

`/admin/member-list` ／ src/Resource/Page/Admin/MemberList.php　<br>前提: authenticated admin。dtb_member に1件以上の管理者が存在すること。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 削除ボタン (各行 type=submit, ラベル '削除') | メンバー行の削除を押す（POST /admin/member?loginId=<Member.loginId>&_method=delete → Member::onDelete） | mode=member_form (hidden), csrfToken=<token>。loginId は action クエリで渡る。 | Member::onDelete が browserForm 時 303 SEE_OTHER → /admin/member-list。再描画された一覧から当該メンバー行が消える（ソフト削除）。 | none に近い。onDelete は browserForm 時 Code::SEE_OTHER + Location='/admin/member-list'。ただし削除後の一覧に削除成功フラッシュが無く、行が消えるのみが観測点。既に削除済みの場合 alreadyDeleted フラグで message を返すが画面表示の有無は要確認。 |
| 編集/検索の hidden mode フォーム (action=SELF method=get, button 無し) | 一覧の絞り込み/再表示（GET self） | mode=member_form (hidden) | 一覧が再描画される（GET なので副作用なし）。 | ⚠️ submit ボタンラベルが空（button:''）。これは表示用フォームで、編集導線は各行の編集リンク経由。検索 UI が無いなら観測上問題なし。MemberList::onGet が mode を解釈するか（無視で全件表示か）を要確認。 |

#### セキュリティ管理 (Security) — Page/Admin/Security.html.twig

`/admin/security` ／ src/Resource/Page/Admin/Security.php　<br>前提: authenticated admin。Anonymous → onGet Code::FORBIDDEN 'この操作には管理者ログインが必要です。'


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (type=submit, ラベル '登録') | 管理ディレクトリ/許可・拒否ホスト/SSL強制/信頼ホストを編集して登録（POST /admin/security?_method=put → onPut） | adminRouteDir=<例 admin>, adminAllowHosts=<...>, adminDenyHosts=<...>, frontAllowHosts=<...>, frontDenyHosts=<...>, forceSsl=<0/1>, trustedHosts=<...>, csrfToken=<token>（各 form.input マクロ既定値） | EC-CUBE faithful: 303 redirect → /admin/security で再表示され、成功フラッシュ 'セキュリティ設定を更新しました。' が表示される。HtmlMutationResponse が Code::SEE_OTHER + Location='/admin/security' を強制。 | ⚠️ onPut は body['message']='セキュリティ設定を更新しました。' を返すが、303 redirect 後の GET 再描画ページにこの message が引き継がれるか要確認。Security テンプレに成功メッセージ表示領域が無ければ（grep では form.error のみで success/alert 無し）、更新成功の明示シグナルが画面に出ない可能性。FLAG: 成功フラッシュ表示要確認。 |
| 登録ボタン — バリデーションエラー系 | adminRouteDir に不正値（許可外文字等）を入れて登録 | adminRouteDir=<不正>, 他はデフォルト, csrfToken=<token> | フォームに留まり該当フィールドのエラー（form.error('adminRouteDir') 等）が表示される。 | ⚠️ テンプレに form.error(field) はあるが、onPut が mutationResponse で即 303 する経路だとバリデーション失敗時にフォーム再描画（BAD_REQUEST + form）に分岐するかを要確認。onPut にエラー分岐が無ければ不正値で例外/500 になりフィールドエラーが出ない恐れ。 |

#### 二要素認証（ログイン時確認） (TwoFactorAuth) — Page/Admin/TwoFactorAuth.html.twig

`/admin/two-factor-auth` ／ src/Resource/Page/Admin/TwoFactorAuth.php　<br>前提: ログイン直後で 2FA 検証チャレンジがセッションに存在する状態（Login::onPost が loginChallenge.startVerification 済み）。チャレンジ無し → Code::FORBIDDEN '二要素認証のログインチャレンジがありません。'


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 認証ボタン (type=submit, ラベル '認証') | 認証アプリのトークンを入力して認証を押す（POST /admin/two-factor-auth） | deviceToken=<6桁コード>, csrfToken=<token> | 成功時 303 SEE_OTHER → /admin/index（管理画面トップ）。browserForm 時 Code::SEE_OTHER + Location='/admin/index'。EC-CUBE faithful の『2段階認証完了→ダッシュボード』に対応。 | none。onPost は browserForm 時 Code::SEE_OTHER + Location='/admin/index'。deviceToken は必須（string、null 不可）— 空送信時に必須エラーがフィールド表示されるか、それとも欠落で 400/例外になるかを要確認（テンプレは form.error('deviceToken') と message を描画）。 |
| 認証ボタン — トークン誤り系 | 誤ったトークンで認証を押す | deviceToken=<誤り>, csrfToken=<token> | 同画面に留まり、認証失敗のフィールドエラー/メッセージ（'認証に失敗しました' 相当）が表示される。 | ⚠️ 要確認: onPost にトークン照合失敗の分岐（rejectForm 相当）が見当たらない。検証失敗が例外で素通りすると 500 になりフィールドエラーが出ない恐れ。失敗時の観測点（field error）を要確認。 |

#### 二要素認証 設定（マイページ編集系） (TwoFactorAuthEdit) — Page/Admin/TwoFactorAuthEdit.html.twig

`/admin/two-factor-auth-edit` ／ src/Resource/Page/Admin/TwoFactorAuthEdit.php　<br>前提: authenticated admin かつ 2FA 設定チャレンジ（authKey 付き）がセッションに存在。adminId 無し→Code::FORBIDDEN、チャレンジ無し→Code::FORBIDDEN '二要素認証の設定チャレンジがありません。'


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (type=submit, ラベル '登録', action=SELF POST) | 認証アプリのトークンを入力して登録を押す（POST self） | authKey=<シークレット/隠し or 入力>, deviceToken=<6桁コード>, csrfToken=<token> | EC-CUBE faithful: 設定完了後 PRG で（管理トップまたは設定画面へ）303 redirect し、成功フラッシュ '二要素認証を設定しました。' が表示される。 | ⚠️ FLAG（不整合）: onPost は成功時 Code::OK(200) を返すのみで headers['Location'] も mutationResponse も無く、リダイレクトしない（兄弟 TwoFactorAuthSet::onPut は /admin/index へ 303 する）。さらにテンプレは成功 message を text-danger（赤/エラー色）の領域に描画するため、成功しても見た目はエラーのよう。ユーザーは『設定できた』ことを正しく認識しづらい。redirect とメッセージ styling の faithful 化が必要。 |
| 登録ボタン — deviceToken 空/誤り系 | deviceToken を空、または誤りで登録 | authKey=<...>, deviceToken=(空 or 誤り), csrfToken=<token> | 同画面に留まり deviceToken のフィールドエラーが表示される（テンプレ form.error('deviceToken')）。 | ⚠️ 要確認: onPost は deviceToken を SetTwoFactorAuthInput に渡すのみで、明示的な照合失敗分岐（rejectForm）が無い。deviceToken は必須 string なので空送信は欠落エラー、誤りは Be 層の例外になりうる。失敗が field error として表示されるか、500 になるかを要確認。 |

#### 二要素認証 設定（ログイン初回設定） (TwoFactorAuthSet) — Page/Admin/TwoFactorAuthSet.html.twig

`/admin/two-factor-auth-set` ／ src/Resource/Page/Admin/TwoFactorAuthSet.php　<br>前提: ログイン直後で 2FA 設定チャレンジ（authKey 付き secret）がセッションに存在（Login::onPost が startSetup 済み）。チャレンジ無し→Code::FORBIDDEN '二要素認証の設定チャレンジがありません。'


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 登録ボタン (type=submit, ラベル '登録', action=/admin/two-factor-auth-set?_method=put → onPut) | QR から登録したアプリのトークンを入力して登録（PUT） | authKey=<セッション secret/hidden>, deviceToken=<6桁コード>, csrfToken=<token> | 成功時 303 SEE_OTHER → /admin/index。browserForm 時 Code::SEE_OTHER + Location='/admin/index'。EC-CUBE faithful の『初回2段階認証設定完了→ダッシュボード』に対応。 | none。onPut は browserForm 時 Code::SEE_OTHER + Location='/admin/index' をセット（TwoFactorAuthEdit と異なり正しくリダイレクトする）。 |
| 登録ボタン — deviceToken 誤り系 | 誤ったトークンで登録 | authKey=<secret>, deviceToken=<誤り>, csrfToken=<token> | 同画面に留まり deviceToken のフィールドエラー/メッセージが表示される。 | ⚠️ 要確認: onPut に照合失敗の rejectForm 分岐が見当たらない。誤りトークンが Be 層例外で 500 になり field error が出ない恐れ。失敗時の観測点を要確認。 |

### admin-misc


#### 管理者パスワード変更 (ChangePassword) — Page/Admin/ChangePassword.html.twig

`/admin/change-password` ／ src/Resource/Page/Admin/ChangePassword.php　<br>前提: authenticated admin (admin session present; adminSession.adminId != null). Anonymous request returns 403. Data: the logged-in admin's real current password must be known so the current_password field can be filled correctly.


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 画面表示 (GET /admin/change-password) | ログイン済み管理者でパスワード変更画面を開く | (GET, no params) | 200 で「パスワード変更」カードが描画され、現在のパスワード / 新しいパスワード / 新しいパスワード(確認) の3入力 (form.input('current_password'\|'change_password_first'\|'change_password_second')) と『登録』ボタンが表示される。hidden csrfToken が input に入っている。 | none — onGet sets Code::OK and body['form'] = AdminChangePasswordForm; template renders the three macro inputs and the 登録 submit button. |
| 画面表示 — 未ログイン時 | 管理者セッション無しで /admin/change-password を開く | (GET, no params) | 403 Forbidden。EC-CUBE 同様、管理ファイアウォールで弾かれ管理ログイン画面相当に到達できない（本文 message='この操作には管理者ログインが必要です。'）。 | none — onGet returns Code::FORBIDDEN when adminSession.adminId === null. Note this is body message, not an HTML login redirect; confirm the html context renders a 403 page rather than a blank. |
| 登録ボタン (正常系: 正しい現在PW + 一致する新PW) | 現在のパスワードに実際のadmin現PWを入れ、新パスワードと確認に同一の有効な新PWを入れて『登録』を押す | current_password=<実際の現PW>, change_password_first=<新PW>, change_password_second=<新PW(同値)>, csrfToken=<有効なCSRF> | EC-CUBE 忠実動作: 303 で /admin/change-password 自身へリダイレクトし、リダイレクト先で成功フラッシュ『パスワードを更新しました』が表示される。 | ⚠️ FAIL — onPost は HtmlMutationResponse 経由で 303 SEE_OTHER + Location=/admin/change-password を返し body['message']='パスワードを変更しました。' を設定するが、303 後に body は破棄される。リダイレクト先 onGet は message/flash を一切 body に積まず、admin-base.html.twig はフラッシュ領域を描画しない（コメントで『flash … 省略』と明記）。結果、ユーザーは空のフォームに戻るだけで成功したか判別不能。これは『成功の可観測シグナルが無い』欠陥そのもの。さらに成功テキストも EC-CUBE は『パスワードを更新しました』だが本文は『パスワードを変更しました。』で不一致。 |
| 登録ボタン (異常系: 現在パスワード誤り) | 現在のパスワードに誤った値、新PW/確認に有効値を入れて『登録』 | current_password=<誤った現PW>, change_password_first=<新PW>, change_password_second=<新PW>, csrfToken=<有効なCSRF> | EC-CUBE 忠実動作: フォームに戻り、現在のパスワード欄に field レベルのバリデーションエラー（『現在のパスワードが間違っています』相当）が inline 表示される。 | ⚠️ FAIL の可能性大 — InvalidCurrentPasswordException は doc 上 400 にマップされるが、これは onPost が Be becoming 内で例外を投げて 400 を返す経路。テンプレートの form.error('current_password') は『form がブリッジされたエラーを持つときだけ』描画される（テンプレ冒頭コメント参照）が、400 例外時に再描画される ChangePassword フォームへエラーが field 名でブリッジされるか未確認。エラーが汎用 400 ページになり、どのフィールドが悪いか・入力値保持が失われるなら EC-CUBE 非忠実。要実機確認。 |
| 登録ボタン (異常系: 新PWと確認が不一致) | 新しいパスワードと確認に別々の値を入れて『登録』 | current_password=<正しい現PW>, change_password_first=<新PW-A>, change_password_second=<新PW-B>, csrfToken=<有効なCSRF> | EC-CUBE 忠実動作: フォームに戻り、新しいパスワード(確認)欄に『パスワードが一致しません』相当の field エラーが inline 表示される。 | ⚠️ FAIL の可能性大 — PasswordConfirmationMismatchException → 400。上と同じく、400 が field 名付きエラーとしてフォームに再描画されるか未確認。汎用 400 なら非忠実。 |
| 登録ボタン (空送信: 全フィールド空) | 何も入力せず『登録』を押す | current_password=(空), change_password_first=(空), change_password_second=(空), csrfToken=<有効なCSRF> | EC-CUBE 忠実動作: 各必須フィールドに『入力してください』相当の field エラーが inline 表示され、保存されない。 | ⚠️ FAIL の可能性 — param schema (post-admin-change-password.param.json) は minLength:0 / null 許容なので transport では 400 にならず、空文字が Be 層へ素通りする。空文字が SemanticVariableException(400) になるか、あるいはサイレントに処理されるかは未確認。いずれにせよ field 単位のエラー表示にならない懸念。要実機確認。 |
| CSRF (不正トークンで送信) | csrfToken を改竄/欠落させて『登録』 | current_password=..., change_password_first=..., change_password_second=..., csrfToken=(不正/欠落) | 403 Forbidden（CSRF インターセプタで拒否）、保存されない。 | none — #[CsrfToken] 属性で 403 にマップ。ただし 403 時にユーザー向けに分かる文言が出るかは未確認（無言の 403 ページ）。 |

#### インストールプラグイン一覧 (PluginList) — Page/Admin/PluginList.html.twig

`/admin/plugin-list` ／ src/Resource/Page/Admin/PluginList.php　<br>前提: authenticated admin。Data: 少なくとも1つのプラグイン行が見えること。SQL ハイパーメディアテストの seedPlugins は Sample/SamplePlugin (有効) と Sample/DisabledPlugin (無効) をシードする。空なら『ユーザー独自プラグインはインストールされていません。』が表示される。


| 操作要素 | アクション | 送信する実値(既定込) | 期待される観測結果 | 現リスク |
|---|---|---|---|---|
| 一覧表示 (GET /admin/plugin-list) | ログイン済み管理者でプラグイン一覧を開く | (GET, no params) | 200。プラグインが1件以上あれば『ユーザー独自プラグイン』カード内のテーブルに各行（プラグイン名 / バージョン / コード / 状態=有効\|無効）が描画される。0件なら『ユーザー独自プラグインはインストールされていません。』。 | none — onGet は GetPluginListInput → PluginListFetched で body['plugins']/['count'] を返し、テンプレが {% for Plugin in plugins %} で描画。EC-CUBE のオーナーズストア(公式)カードは projection に無く意図的に省略（残差として明記）。 |
| 『アップロードして新規追加』ボタン | カードヘッダの『アップロードして新規追加』を押す | (GET /admin/plugin-list — <a href="/admin/plugin-list">) | EC-CUBE 忠実動作: プラグインのアップロード/インストール画面（zip アップロードフォーム = admin_store_plugin_install）へ遷移し、ファイルを選んでインストールできる。 | ⚠️ FAIL — このアンカーは単なる <a href="/admin/plugin-list"> で、同じ一覧画面へ戻るだけ。EC-CUBE のインストール画面/アップロードフォームは移植されていない（テンプレ冒頭コメントで plugin upload form を残差として省略と明記）。新規プラグイン追加という affordance がユーザーに提供されていない。 |
| doInstallPlugin (POST /admin/plugin-list — ALPS の install affordance) | プラグインを新規インストール（resource は pluginCode/pluginName/pluginVersion を要求） | pluginCode=<例: Sample/SamplePlugin>, pluginName=<例: Sample Plugin>, pluginVersion=<例: 1.0.0> (この3つは param schema で required) | EC-CUBE 忠実動作: インストール完了で一覧へリダイレクトし成功フラッシュ『プラグインをインストールしました』相当が表示され、一覧に新プラグイン行が現れる。 | ⚠️ FAIL (no UI affordance) — onPost(doInstallPlugin) は存在し CREATED/OK を返すが、screen_forms.json の POST フォームは submit:false / button:null / fields:[] で、テンプレートに pluginCode/pluginName/pluginVersion を送る <form>・送信ボタンが一切無い。HTML 画面からこの POST を発火する手段が無く、no-JS でも JS でも到達不能。さらに onPost は HtmlMutationResponse を使わず生の Code を返すため、仮に発火しても 303 リダイレクトもフラッシュも無く成功は不可視。 |
| 有効にする (再生アイコン) | 無効なプラグイン行の ▶ アイコンを押す | (JS anchor) <a href="/admin/plugin-enable?pluginCode={code}" data-method="post" data-confirm="false"> — ブラウザの素の遷移では GET /admin/plugin-enable?pluginCode=... になる | EC-CUBE 忠実動作: POST で有効化され、一覧へリダイレクトし成功フラッシュ『プラグインを有効にしました』相当が表示され、当該行の状態が『無効』→『有効』に変わる。 | ⚠️ FAIL — これは data-method=post の JS 依存アンカー。no-JS では href の GET /admin/plugin-enable?pluginCode=... に遷移する（POST にならない）。enable は別 resource(plugin-enable) で本グループ外だが、この画面上のトリガとして: (1) JS 無効で動かない、(2) 成功後に一覧へ戻ってフラッシュで状態変化を知らせる経路が admin-base にフラッシュ領域が無いため不可視になる懸念。要 plugin-enable resource 側確認。 |
| 無効にする (一時停止アイコン) | 有効なプラグイン行の ⏸ アイコンを押す | (JS anchor) <a href="/admin/plugin-disable?pluginCode={code}" data-method="post" data-confirm="false"> — 素の遷移では GET /admin/plugin-disable?pluginCode=... | EC-CUBE 忠実動作: POST で無効化され一覧へリダイレクト、成功フラッシュ『プラグインを無効にしました』相当が表示され、行の状態が『有効』→『無効』に変わる（無効化により削除アイコンも出現）。 | ⚠️ FAIL — enable と同じく data-method=post の JS 依存アンカー。no-JS では GET に落ちる。状態変化は再表示で確認できるが、成功フラッシュ領域が admin-base に無いため『無効にしました』の確認が出ない。 |
| 削除アイコン → 削除モーダル → 『削除』 | 無効なプラグイン行の × を押し、確認モーダルで『削除』を押す | (JS) × アイコンの data-delete-url="/admin/plugin?pluginCode={code}&_method=delete" をモーダルの『削除』<a> に流し込み、DELETE /admin/plugin?pluginCode=... を送る想定 | EC-CUBE 忠実動作: DELETE でアンインストールされ一覧へリダイレクト、成功フラッシュ『プラグインを削除しました』相当が表示され、当該行が一覧から消える。 | ⚠️ FAIL — モーダル内の『削除』<a href="#"> には data-delete-url が結線されておらず（× アイコン側にのみ data-delete-url がある）、JS が delete-url をモーダルのボタンへ移送する想定だが、その JS（localPluginDeleteModal のハンドラ）はこのテンプレに無い。changeActionSubmit しか定義されておらず削除導線が未結線。no-JS では完全に不動作、JS でも『削除』ボタンが空 href のまま。行が消える可観測結果に到達できない可能性が高い。要実機確認。 |
| 0件時メッセージ | プラグインが1件も無い状態で一覧を開く | (GET, no params) | 『ユーザー独自プラグインはインストールされていません。』が text-danger で表示される。 | none — {% else %} 分岐で表示。 |
