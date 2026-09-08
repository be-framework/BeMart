<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Module;

use BEAR\Package\Provide\Representation\RouterReverseLinker;
use BEAR\Resource\ReverseLinkerInterface;
use BEAR\Sunday\Extension\Error\ThrowableHandlerInterface;
use Madapaja\TwigModule\TwigModule;
use MyVendor\BeMart\Auth\AdminSessionWriterInterface;
use MyVendor\BeMart\Auth\CartSessionPrefixInterface;
use MyVendor\BeMart\Auth\HtmlAdminSessionAdapter;
use MyVendor\BeMart\Auth\HtmlAdminSessionWriter;
use MyVendor\BeMart\Auth\HtmlCartSessionPrefix;
use MyVendor\BeMart\Auth\HtmlCustomerSessionWriter;
use MyVendor\BeMart\Auth\CustomerSessionWriterInterface;
use MyVendor\BeMart\Be\Reason\Service\AdminSession;
use MyVendor\BeMart\Provide\Error\HtmlThrowableHandler;
use MyVendor\BeMart\Provide\Render\AdminAuthRedirectRenderer;
use MyVendor\BeMart\Provide\Transfer\DownloadContentTypePolicyInterface;
use MyVendor\BeMart\Provide\Transfer\HtmlDownloadContentTypePolicy;
use MyVendor\BeMart\Support\Html\HtmlLinkAuditLoggerInterface;
use MyVendor\BeMart\Support\Html\LinkHeaderModule;
use MyVendor\BeMart\Support\Html\SilentHtmlLinkAuditLogger;
use MyVendor\BeMart\Support\Resource\AdminLoginFormSubmissionInterface;
use MyVendor\BeMart\Support\Resource\HtmlAdminLoginFormSubmission;
use MyVendor\BeMart\Support\Resource\HtmlMutationResponse;
use MyVendor\BeMart\Support\Resource\MutationResponseInterface;
use Override;
use Ray\Di\AbstractModule;
use Ray\WebFormModule\WebFormModule;
use Twig\Environment;

/** HTML presentation modifier. It does not choose SQL/Fake/Test persistence. */
final class HtmlModule extends AbstractModule
{
    /** @param array<string, mixed> $twigOptions */
    public function __construct(
        AbstractModule|null $module = null,
        private readonly array $twigOptions = [],
    ) {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        // Admin firewall UX: Page\Admin 403s reach the browser as a 303 to
        // the login page, not as a template rendered with an empty body.
        // See AdminAuthRedirectRenderer. Resource-level 403s are unchanged.
        $this->override(new LinkHeaderModule(new AdminAuthRedirectModule(new TwigModule(options: $this->twigOptions))));
        $this->bind(ReverseLinkerInterface::class)->to(RouterReverseLinker::class);
        $this->bind(AdminSession::class)->to(HtmlAdminSessionAdapter::class);
        $this->bind(CustomerSessionWriterInterface::class)->to(HtmlCustomerSessionWriter::class);
        $this->bind(AdminSessionWriterInterface::class)->to(HtmlAdminSessionWriter::class);
        $this->bind(CartSessionPrefixInterface::class)->to(HtmlCartSessionPrefix::class);
        $this->bind(DownloadContentTypePolicyInterface::class)->to(HtmlDownloadContentTypePolicy::class);
        $this->bind(MutationResponseInterface::class)->to(HtmlMutationResponse::class);
        $this->bind(AdminLoginFormSubmissionInterface::class)->to(HtmlAdminLoginFormSubmission::class);
        $this->bind(Environment::class)->toProvider(HtmlTwigEnvironmentProvider::class);
        // Browser users get an HTML error page; the JSON AppThrowableHandler
        // (AppErrorModule) stays the default for API/HAL contexts.
        $this->bind(ThrowableHandlerInterface::class)->to(HtmlThrowableHandler::class);
        $this->install(new WebFormModule());
    }
}
