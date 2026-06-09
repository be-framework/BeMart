<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support;

use MyVendor\BeMart\Injector as AppInjector;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\InjectorInterface;

final class HtmlTestInjector
{
    private const CONTEXT = 'html-test-hal-app';

    /** @codeCoverageIgnore */
    private function __construct()
    {
    }

    public static function getInstance(): InjectorInterface
    {
        // Empty override is intentional: html-test-hal-app now exercises standard context composition
        // with HtmlModule's default Twig options, not the removed HtmlTestModule debug/cache flags.
        return AppInjector::getOverrideInstance(self::CONTEXT, new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
            }
        });
    }

    public static function getOverrideInstance(AbstractModule $overrideModule): InjectorInterface
    {
        return AppInjector::getOverrideInstance(self::CONTEXT, $overrideModule);
    }
}
