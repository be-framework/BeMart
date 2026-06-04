<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /shopping/login
EC-CUBE goShoppingLogin — 購入ログイン (Wave 3H pure renderer).

Pure form-info endpoint: no Be Framework, no domain logic, no Reasons.
Anonymous-accessible (this IS the unauthenticated branch of the
checkout flow). Maps to `page://self/shopping/login`.

Reached when an anonymous visitor hits `goShopping`. Three exits:
member login (doLogin), customer registration (goCustomerRegistration),
or non-member purchase (goShoppingNonMember). The page itself carries a
login form (the same `CustomerLoginType` shape as the standalone
`goLogin` page) plus the guest-purchase link.

Phase 3 — HTML FORM page. `Shopping/login.twig` renders the login
inputs through the Symfony FormView; BeMart exposes a {@see \LoginForm}
(the same AbstractForm the standalone Login page uses — EC-CUBE's
`shopping_login` route reuses `CustomerLoginType`) as `body['form']`
so the HTML port renders real `<input>`s via `{{ form.input(...) }}`.
The form is a field-definition + renderer only.

Coexists with `Resource\Page\Shopping\Checkout.php` (Pilot 5) and
`Shopping\NonMember.php` (Wave 7W).




## GET


### Request

_No parameters required_

### Response

_Not available_