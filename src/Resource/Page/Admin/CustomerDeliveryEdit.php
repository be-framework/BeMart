<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Resource\Page\Admin;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\BeMart\Be\Reason\Service\AdminSessionInterface;
use MyVendor\BeMart\Form\AdminCustomerDeliveryForm;
use Ray\WebFormModule\FormFactory;

use function assert;

/**
 * EC-CUBE お届け先編集 — Customer Tier-2.
 *
 * Thin GET renderer for `admin/Customer/delivery_edit.twig`, the customer
 * address-book entry editor. BeMart has no ALPS transition for persisting
 * a customer address in this wave, so the page exposes the empty
 * edit-form body shape for HTML rendering only — completing the Customer
 * section alongside the already-ported list/edit pages.
 *
 * Admin-only — the AUTHZ guard rejects an anonymous admin with 403,
 * matching the sibling Setting/System Tier-2 renderers ({@see System},
 * {@see Security}, {@see TwoFactorAuthEdit}).
 */
class CustomerDeliveryEdit extends ResourceObject
{
    public function __construct(
        private readonly AdminSessionInterface $adminSession,
        private readonly FormFactory $formFactory,
    ) {
    }

    /**
     * The customer id comes from the admin UI (route param), so it is
     * user-controlled — same taint discipline as the sibling
     * {@see Customer} resource.
     *
     * @psalm-taint-source input $customerId
     */
    #[Link(rel: 'goCustomerList', href: 'page://self/admin/customer-list')]
    public function onGet(string $customerId = ''): static
    {
        if ($this->adminSession->adminId() === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'この操作には管理者ログインが必要です。'];

            return $this;
        }

        $form = $this->formFactory->newInstance(AdminCustomerDeliveryForm::class);
        assert($form instanceof AdminCustomerDeliveryForm);

        $this->code = Code::OK;
        $this->body = [
            'form' => $form,
            'customerId' => $customerId,
        ];

        return $this;
    }
}
