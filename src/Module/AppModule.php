<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\Module\AppMetaModule;
use BEAR\Package\PackageModule;
use Be\Framework\Module\BeModule;
use MyVendor\BeMart\Be\Reason\Query\AdminMasterRegistry;
use MyVendor\BeMart\Be\Reason\Query\AdminMasterRegistryInterface;
use MyVendor\BeMart\Be\Reason\Service\ResetKeyGenerator;
use MyVendor\BeMart\Be\Reason\Service\ResetKeyGeneratorInterface;
use Ray\WebFormModule\FormFactory;

/**
 * Shared application module.
 *
 * This module is intentionally production-neutral: it installs BEAR/Be
 * framework infrastructure and bindings that are valid in every context, but
 * it does not bind Fake Reasons, dev logging, sessions, CSRF adapters, or SQL.
 * Context modules compose those concerns explicitly.
 */
final class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        $this->install(new PackageModule());
        // PackageModule does not bind @AppName by itself; BEAR\Package\Module
        // factory normally overrides it. Install explicitly so tests can use
        // `new Injector(new *Module(...))` without the factory.
        $this->override(new AppMetaModule($this->appMeta));

        // Be Framework: BecomingInterface, SemanticLogger, semantic validator,
        // Been provider. Dev/Test contexts override logging with DevLoggingModule;
        // prod keeps BeModule's plain Becoming/SemanticLogger bindings.
        $this->install(new BeModule('MyVendor\\BeMart\\Be\\Semantic'));

        // The reset key is an unguessable one-time token — minted by a
        // dedicated CSPRNG-backed generator, NOT a customer-id generator.
        $this->bind(ResetKeyGeneratorInterface::class)->to(ResetKeyGenerator::class);

        // Shared registry over master storage interfaces. The storage
        // implementations come from the active persistence module (Fake or SQL).
        $this->bind(AdminMasterRegistryInterface::class)->to(AdminMasterRegistry::class);

        // FormFactory builds AbstractForm instances with their Aura.Input /
        // Aura.Filter / Aura.Html dependencies self-contained. It is cheap in
        // JSON contexts and rendered only by HtmlModule.
        $this->bind(FormFactory::class);
    }
}
