<a href="../index.md" style="color: black; text-decoration: none;">BeMart Page Resource API Doc</a>

# /forgot-complete
EC-CUBE goForgotComplete — パスワード再発行(メール送信完了) (Phase 3
pure renderer).

Pure renderer: no Be Framework, no domain logic, no Reasons — the same
shape as {@see \Products}. EC-CUBE shows `Forgot/complete.twig` after a
successful `doRequestPasswordReset`; it is a static confirmation page
with NO form (data-page recipe).

Anonymous-accessible (returns 200 regardless of session state). Maps
to `page://self/forgot-complete`. The companion {@see \ForgotPassword}
resource owns the actual reset-request domain logic; this resource
carries only the confirmation page's hypermedia surface — the page
itself is static text ported from EC-CUBE's template.

Why a dedicated renderer (not a branch of ForgotPassword): EC-CUBE's
`doRequestPasswordReset` controller renders `Forgot/index.twig` on the
request screen and `Forgot/complete.twig` on completion — two distinct
templates, two distinct pages. BeMart's `ForgotPassword::onPost` is the
anti-enumeration request endpoint (uniform 200); this resource is the
separate confirmation page so each template has a 1:1 resource.




## GET
EC-CUBE goForgotComplete — render the reset-mail-sent confirmation
page scaffolding.



### Request

_No parameters required_

### Response

_Not available_