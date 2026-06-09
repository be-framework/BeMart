<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Tests\Support\Hypermedia;

use Closure;
use MyVendor\BeMart\Be\Reason\Entity\CustomerEntity;
use MyVendor\BeMart\Be\Reason\Entity\LayoutEntity;
use MyVendor\BeMart\Be\Reason\Entity\MailTemplateEntity;
use MyVendor\BeMart\Be\Reason\Query\CustomerCommandInterface;
use MyVendor\BeMart\Be\Reason\Query\LayoutStorageInterface;
use MyVendor\BeMart\Be\Reason\Query\MailTemplateStorageInterface;
use Ray\Di\InjectorInterface;

use function array_reverse;
use function assert;
use function sprintf;

/**
 * Fixture boundary for workflow rows that must be visible to HTTP projection.
 *
 * Class rollback transactions protect mutations made by the in-process resource
 * connection. Rows created inside those transactions, however, are invisible to
 * the HTTP projection because it runs through a separate PHP process / DB
 * connection. Seed those cross-connection rows through this boundary before the
 * class rollback transaction starts, then call {@see cleanup()} after rollback.
 *
 * Cleanup stays on Be storage/command contracts instead of issuing raw PDO SQL,
 * so each fixture's lifecycle is visible at the workflow-support boundary.
 */
final class WorkflowFixtureBoundary
{
    /** @var list<Closure(): void> */
    private array $cleanupCallbacks = [];

    private function __construct(
        private readonly InjectorInterface $injector,
    ) {
    }

    public static function fromInjector(InjectorInterface $injector): self
    {
        return new self($injector);
    }

    /**
     * Make a layout row visible to HTTP projection.
     *
     * LayoutStorageInterface intentionally has no delete transition. When a
     * baseline row already exists, cleanup restores that exact row. If the row
     * did not exist, the seed remains as an explicit reusable baseline because
     * deleting layouts is outside the current ALPS/resource contract.
     */
    public function makeLayoutVisible(LayoutEntity $layout): void
    {
        $layouts = $this->layoutStorage();
        $previous = $layouts->item($layout->layoutId);

        $layouts->put($layout);

        if (! $previous instanceof LayoutEntity) {
            return;
        }

        $this->cleanupCallbacks[] = static function () use ($layouts, $previous): void {
            $layouts->put($previous);
        };
    }

    /**
     * Seed the mail-template list only when the database has no visible row.
     */
    public function ensureMailTemplateListVisible(MailTemplateEntity $mailTemplate): void
    {
        $mailTemplates = $this->mailTemplateStorage();
        if ($mailTemplates->list() !== []) {
            return;
        }

        $mailTemplates->put($mailTemplate);
        $this->cleanupCallbacks[] = static function () use ($mailTemplates, $mailTemplate): void {
            $mailTemplates->delete($mailTemplate->mailTemplateId);
        };
    }

    /**
     * Register a provisional customer that activation HTTP projection can see.
     *
     * Customer storage follows EC-CUBE's soft-delete convention, so cleanup uses
     * the public update command to withdraw the seeded row instead of physically
     * deleting it.
     */
    public function registerActivationCustomer(CustomerEntity $customer): void
    {
        $customers = $this->customerCommand();
        $customers->register($customer);

        $this->cleanupCallbacks[] = static function () use ($customers, $customer): void {
            $customers->update(self::withdrawnCustomer($customer));
        };
    }

    public function cleanup(): void
    {
        foreach (array_reverse($this->cleanupCallbacks) as $cleanup) {
            $cleanup();
        }

        $this->cleanupCallbacks = [];
    }

    private function layoutStorage(): LayoutStorageInterface
    {
        $layouts = $this->injector->getInstance(LayoutStorageInterface::class);
        assert($layouts instanceof LayoutStorageInterface);

        return $layouts;
    }

    private function mailTemplateStorage(): MailTemplateStorageInterface
    {
        $mailTemplates = $this->injector->getInstance(MailTemplateStorageInterface::class);
        assert($mailTemplates instanceof MailTemplateStorageInterface);

        return $mailTemplates;
    }

    private function customerCommand(): CustomerCommandInterface
    {
        $customers = $this->injector->getInstance(CustomerCommandInterface::class);
        assert($customers instanceof CustomerCommandInterface);

        return $customers;
    }

    private static function withdrawnCustomer(CustomerEntity $customer): CustomerEntity
    {
        return new CustomerEntity(
            customerId: $customer->customerId,
            email: sprintf('withdrawn-%s@example.invalid', $customer->customerId),
            passwordHash: $customer->passwordHash,
            name01: $customer->name01,
            name02: $customer->name02,
            kana01: $customer->kana01,
            kana02: $customer->kana02,
            companyName: $customer->companyName,
            phoneNumber: $customer->phoneNumber,
            postalCode: $customer->postalCode,
            pref: $customer->pref,
            addr01: $customer->addr01,
            addr02: $customer->addr02,
            birth: $customer->birth,
            sex: $customer->sex,
            job: $customer->job,
            initialPoint: $customer->initialPoint,
            customerStatus: 3,
            secretKey: $customer->secretKey,
        );
    }
}
