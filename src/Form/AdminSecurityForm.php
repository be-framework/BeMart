<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Form;

use Override;
use Ray\WebFormModule\AbstractForm;

/**
 * EC-CUBE セキュリティ管理フォーム — Setting/System Tier-2.
 *
 * PORT of EC-CUBE 4.3 `Form/Type/Admin/SecurityType` + the
 * `admin/Setting/System/security.twig` widgets. The form is a renderer
 * only; BeMart has no ALPS transition for persisting the config file
 * values in this wave.
 */
final class AdminSecurityForm extends AbstractForm
{
    #[Override]
    public function init(): void
    {
        $this->setField('admin_route_dir', 'text')
            ->setAttribs([
                'id' => 'admin_security_admin_route_dir',
                'class' => 'form-control',
            ]);

        $this->setField('admin_allow_hosts', 'textarea')
            ->setAttribs([
                'id' => 'admin_security_admin_allow_hosts',
                'class' => 'form-control',
                'rows' => '8',
                'placeholder' => "192.0.2.0/24\n203.0.113.10",
            ]);

        $this->setField('admin_deny_hosts', 'textarea')
            ->setAttribs([
                'id' => 'admin_security_admin_deny_hosts',
                'class' => 'form-control',
                'rows' => '8',
                'placeholder' => "192.0.2.0/24\n203.0.113.10",
            ]);

        $this->setField('front_allow_hosts', 'textarea')
            ->setAttribs([
                'id' => 'admin_security_front_allow_hosts',
                'class' => 'form-control',
                'rows' => '8',
                'placeholder' => "192.0.2.0/24\n203.0.113.10",
            ]);

        $this->setField('front_deny_hosts', 'textarea')
            ->setAttribs([
                'id' => 'admin_security_front_deny_hosts',
                'class' => 'form-control',
                'rows' => '8',
                'placeholder' => "192.0.2.0/24\n203.0.113.10",
            ]);

        $this->setField('force_ssl', 'checkbox')
            ->setAttribs([
                'id' => 'admin_security_force_ssl',
            ])
            ->setOptions(['1' => 'SSLを強制']);

        $this->setField('trusted_hosts', 'text')
            ->setAttribs([
                'id' => 'admin_security_trusted_hosts',
                'class' => 'form-control',
                'placeholder' => '^www\\.example\\.com$',
            ]);

        $this->filter->validate('admin_route_dir')->isNotBlank();
        $this->filter->validate('trusted_hosts')->isNotBlank();
    }

    /**
     * @param array<string, scalar|null> $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
