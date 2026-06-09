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
        $this->setField('adminRouteDir', 'text')
            ->setAttribs([
                'id' => 'admin_security_admin_route_dir',
                'class' => 'form-control',
            ]);

        $this->setField('adminAllowHosts', 'textarea')
            ->setAttribs([
                'id' => 'admin_security_admin_allow_hosts',
                'class' => 'form-control',
                'rows' => '8',
                'placeholder' => "192.0.2.0/24\n203.0.113.10",
            ]);

        $this->setField('adminDenyHosts', 'textarea')
            ->setAttribs([
                'id' => 'admin_security_admin_deny_hosts',
                'class' => 'form-control',
                'rows' => '8',
                'placeholder' => "192.0.2.0/24\n203.0.113.10",
            ]);

        $this->setField('frontAllowHosts', 'textarea')
            ->setAttribs([
                'id' => 'admin_security_front_allow_hosts',
                'class' => 'form-control',
                'rows' => '8',
                'placeholder' => "192.0.2.0/24\n203.0.113.10",
            ]);

        $this->setField('frontDenyHosts', 'textarea')
            ->setAttribs([
                'id' => 'admin_security_front_deny_hosts',
                'class' => 'form-control',
                'rows' => '8',
                'placeholder' => "192.0.2.0/24\n203.0.113.10",
            ]);

        $this->setField('forceSsl', 'checkbox')
            ->setAttribs([
                'id' => 'admin_security_force_ssl',
            ])
            ->setOptions(['1' => 'SSLを強制']);

        $this->setField('trustedHosts', 'text')
            ->setAttribs([
                'id' => 'admin_security_trusted_hosts',
                'class' => 'form-control',
                'placeholder' => '^www\\.example\\.com$',
            ]);

        $this->filter->validate('adminRouteDir')->isNotBlank();
        $this->filter->validate('trustedHosts')->isNotBlank();
    }

    /**
     * @param array<string, scalar|null> $values
     */
    public function fillValues(array $values): void
    {
        $this->fill($values);
    }
}
