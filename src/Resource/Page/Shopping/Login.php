<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Shopping;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Form\LoginForm;
use Ray\WebFormModule\FormFactory;
use BEAR\Resource\Annotation\JsonSchema;

/**
 * EC-CUBE goShoppingLogin — 購入ログイン (Wave 3H pure renderer).
 *
 * Pure form-info endpoint: no Be Framework, no domain logic, no Reasons.
 * Anonymous-accessible (this IS the unauthenticated branch of the
 * checkout flow). Maps to `page://self/shopping/login`.
 *
 * Reached when an anonymous visitor hits `goCheckoutEntry`. Three exits:
 * member login (doLogin), customer registration (goCustomerRegistration),
 * or non-member purchase (goShoppingNonMember). The page itself carries a
 * login form (the same `CustomerLoginType` shape as the standalone
 * `goLogin` page) plus the guest-purchase link.
 *
 * Phase 3 — HTML FORM page. `Shopping/login.twig` renders the login
 * inputs through the Symfony FormView; BeMart exposes a {@see LoginForm}
 * (the same AbstractForm the standalone Login page uses — EC-CUBE's
 * `shopping_login` route reuses `CustomerLoginType`) as `body['form']`
 * so the HTML port renders real `<input>`s via `{{ form.input(...) }}`.
 * The form is a field-definition + renderer only.
 *
 * Coexists with `Resource\Page\Shopping\Checkout.php` (Pilot 5) and
 * `Shopping\NonMember.php` (Wave 7W).
 */
class Login extends ResourceObject
{
    public function __construct(
        private readonly FormFactory $formFactory,
    ) {
    }

    /** ALPS `goShoppingLogin` に対応する GET 操作。 */
    #[Alps('goShoppingLogin')]
    #[JsonSchema(schema: 'get-shopping-login.json')]
    #[Link(rel: 'doLogin', href: 'page://self/login', method: 'post')]
    #[Link(rel: 'goCustomerRegistration', href: 'page://self/entry')]
    #[Link(rel: 'goShoppingNonMember', href: 'page://self/shopping/non-member')]
    public function onGet(): static
    {
        $this->code = Code::OK;
        $this->body = [
            'transitionId' => 'goShoppingLogin',
            'fields' => [],
            'submitTo' => null,
            'staticContent' => null,
            'links' => [
                'doLogin' => 'page://self/login',
                'goCustomerRegistration' => 'page://self/entry',
                'goShoppingNonMember' => 'page://self/shopping/non-member',
            ],
            // Phase 3: an empty LoginForm for the HTML port to render
            // the checkout-login inputs. JSON contexts ignore it.
            'form' => $this->formFactory->newInstance(LoginForm::class),
        ];

        return $this;
    }
}
