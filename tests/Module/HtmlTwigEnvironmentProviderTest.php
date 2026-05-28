<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Module;

use Aura\Router\RouterContainer;
use MyVendor\BeMart\Be\Reason\Fake\Service\FakeCsrfToken;
use MyVendor\BeMart\Module\BeMartTwigExtension;
use MyVendor\BeMart\Module\HtmlTwigEnvironmentProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class HtmlTwigEnvironmentProviderTest extends TestCase
{
    public function testEnablesAutoReloadOnDecoratedTwigEnvironment(): void
    {
        $twig = new Environment(new ArrayLoader([]), [
            'auto_reload' => false,
            'cache' => false,
        ]);

        self::assertFalse($twig->isAutoReload());

        $provider = new HtmlTwigEnvironmentProvider($twig, $this->routerContainer(), new FakeCsrfToken());
        $provided = $provider->get();

        self::assertSame($twig, $provided);
        self::assertTrue($provided->isAutoReload());
        self::assertTrue($provided->hasExtension(BeMartTwigExtension::class));
    }

    private function routerContainer(): RouterContainer
    {
        $container = new RouterContainer();
        /** @var callable(\Aura\Router\Map): null $routes */
        $routes = require __DIR__ . '/../../config/aura-routes.php';
        $container->setMapBuilder($routes);

        return $container;
    }
}
