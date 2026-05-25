<?php

declare(strict_types=1);

namespace MyVendor\BeMart;

use BEAR\AppMeta\Meta;
use MyVendor\BeMart\Module\DevFakeHalApiModule;
use MyVendor\BeMart\Module\FakeModule;
use MyVendor\BeMart\Module\HalApiModule;
use MyVendor\BeMart\Module\HtmlHalModule;
use MyVendor\BeMart\Module\HtmlProdModule;
use MyVendor\BeMart\Module\HtmlTestModule;
use MyVendor\BeMart\Module\ProdModule;
use MyVendor\BeMart\Module\TestModule;
use Ray\Di\AbstractModule;
use Ray\Di\Injector as RayInjector;
use Ray\Di\InjectorInterface;

use function dirname;
use function sprintf;

final class Injector
{
    private function __construct()
    {
    }

    /** @param non-empty-string $context */
    public static function getInstance(string $context): InjectorInterface
    {
        $appDir = dirname(__DIR__);
        $meta = new Meta(__NAMESPACE__, $context, $appDir);

        return new RayInjector(self::module($context, $meta), $meta->tmpDir);
    }

    /** @param non-empty-string $context */
    private static function module(string $context, Meta $meta): AbstractModule
    {
        return match ($context) {
            'hal-api-app', 'cli-hal-api-app' => new HalApiModule($meta),
            'fake-hal-api-app', 'cli-fake-hal-api-app' => new FakeModule($meta),
            'dev-fake-hal-api-app', 'cli-dev-fake-hal-api-app' => new DevFakeHalApiModule($meta),
            'test-hal-api-app', 'cli-test-hal-api-app' => new TestModule($meta),
            'html-hal-app', 'cli-html-hal-app' => new HtmlHalModule($meta),
            'html-test-hal-api-app', 'cli-html-test-hal-api-app' => new HtmlTestModule($meta),
            'prod-hal-api-app', 'cli-prod-hal-api-app' => new ProdModule($meta),
            'html-prod-hal-api-app', 'cli-html-prod-hal-api-app' => new HtmlProdModule($meta),
            default => throw new AppContextModuleNotFoundException(sprintf('Unknown app context: %s', $context)),
        };
    }
}
