<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionNamedType;
use SensitiveParameter;
use SplFileInfo;

use function class_exists;
use function dirname;
use function implode;
use function preg_match;
use function sprintf;
use function strlen;
use function str_replace;
use function substr;

/**
 * Boundary contract: every Be-layer (Input/Final/Being) string constructor
 * parameter whose name looks like a credential must be marked
 * #[SensitiveParameter], so the constructor's own stack frame redacts the
 * value when an exception propagates through it — only that one frame is
 * protected, not the whole trace or any copy of the value held elsewhere
 * on a property.
 *
 * Discovery-based, not an allowlist: a manual audit had already missed
 * seven of these (RegisterCustomerInput/AdminCreateCustomerInput/
 * CreateMemberInput::$password, SetTwoFactorAuthInput/
 * VerifyTwoFactorAuthInput/TwoFactorAuthConfigured/
 * TwoFactorAuthVerified::$deviceToken), which is why every class under
 * be/src/Input, be/src/Final and be/src/Being is reflected here instead of
 * hand-listed, so a new credential-shaped parameter fails this test until
 * it is annotated.
 */
final class CredentialConstructorParameterSensitiveParameterTest extends TestCase
{
    private const NAMESPACE_PREFIX = 'MyVendor\\BeMart\\Be\\';
    private const CREDENTIAL_NAME = '/password|secret|token|resetKey|authKey/i';

    public function testEveryCredentialShapedConstructorParameterIsMarkedSensitive(): void
    {
        $srcDir = dirname(__DIR__, 2) . '/src';
        $checked = 0;
        $violations = [];

        foreach (['Input', 'Final', 'Being'] as $layer) {
            $layerDir = $srcDir . '/' . $layer;
            if (! is_dir($layerDir)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($layerDir, FilesystemIterator::SKIP_DOTS),
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $relative = substr($file->getPathname(), strlen($srcDir) + 1);
                $class = self::NAMESPACE_PREFIX . str_replace(['/', '.php'], ['\\', ''], $relative);
                if (! class_exists($class)) {
                    continue;
                }

                $constructor = (new ReflectionClass($class))->getConstructor();
                if ($constructor === null) {
                    continue;
                }

                foreach ($constructor->getParameters() as $parameter) {
                    if (preg_match(self::CREDENTIAL_NAME, $parameter->getName()) !== 1) {
                        continue;
                    }

                    // Only raw string-shaped values are candidate credentials;
                    // injected services (e.g. #[Inject] PasswordHasherInterface
                    // $passwordHasher) match the name pattern too but carry no
                    // secret of their own.
                    $type = $parameter->getType();
                    if (! $type instanceof ReflectionNamedType || $type->getName() !== 'string') {
                        continue;
                    }

                    $checked++;
                    if ($parameter->getAttributes(SensitiveParameter::class) !== []) {
                        continue;
                    }

                    $violations[] = sprintf('%s::$%s', $class, $parameter->getName());
                }
            }
        }

        $this->assertGreaterThan(0, $checked, 'discovery found no credential-shaped constructor parameters; the walker or pattern is broken');
        $this->assertSame(
            [],
            $violations,
            "credential-shaped constructor parameters without #[SensitiveParameter]:\n  " . implode("\n  ", $violations),
        );
    }
}
