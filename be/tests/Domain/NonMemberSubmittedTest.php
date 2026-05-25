<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Tests\Domain;

use BEAR\AppMeta\Meta;
use Be\Framework\BecomingInterface;
use Be\Framework\Exception\SemanticVariableException;
use MyVendor\BeMart\Be\Exception\EmailFormatException;
use MyVendor\BeMart\Be\Final\NonMemberSubmitted;
use MyVendor\BeMart\Be\Input\SubmitNonMemberInput;
use MyVendor\BeMart\Module\TestModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function dirname;

final class NonMemberSubmittedTest extends TestCase
{
    private BecomingInterface $becoming;

    protected function setUp(): void
    {
        $injector = new Injector(
            new TestModule(new Meta('MyVendor\\BeMart', 'test')),
            dirname(__DIR__, 2) . '/var/tmp/test',
        );
        $this->becoming = $injector->getInstance(BecomingInterface::class);
    }

    public function testHappyPathReturnsFinalWithSynthesisedPreOrderId(): void
    {
        $final = ($this->becoming)(new SubmitNonMemberInput(
            name01: '田中',
            name02: '太郎',
            kana01: 'タナカ',
            kana02: 'タロウ',
            email: 'guest@example.com',
            phoneNumber: '0312345678',
            postalCode: '1500001',
            pref: 13,
            addr01: '渋谷区',
            addr02: '神宮前1-2-3',
        ));

        $this->assertInstanceOf(NonMemberSubmitted::class, $final);
        $this->assertSame('田中', $final->name01);
        $this->assertSame('太郎', $final->name02);
        $this->assertSame('guest@example.com', $final->email);
        // Wave 7W reuses CustomerIdGenerator (32-char hex). Phase 2
        // will dedicate a PreOrderIdGenerator aligned with the
        // PreOrderId Semantic's 40-hex format.
        $this->assertMatchesRegularExpression('/\A[0-9a-f]{32}\z/', $final->preOrderId);
    }

    public function testInvalidEmailRejected(): void
    {
        $this->expectException(SemanticVariableException::class);
        try {
            ($this->becoming)(new SubmitNonMemberInput(
                name01: '田中',
                name02: '太郎',
                kana01: 'タナカ',
                kana02: 'タロウ',
                email: 'not-an-email',
                phoneNumber: '0312345678',
                postalCode: '1500001',
                pref: 13,
                addr01: '渋谷区',
                addr02: '神宮前1-2-3',
            ));
        } catch (SemanticVariableException $e) {
            $this->assertInstanceOf(
                EmailFormatException::class,
                $e->getErrors()->exceptions[0],
            );

            throw $e;
        }
    }
}
