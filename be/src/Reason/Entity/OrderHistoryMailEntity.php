<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Reason\Entity;

/**
 * One mail-delivery-history row inside an {@see OrderHistoryEntity}.
 *
 * Phase 3 enrichment — the order-history detail screen (goMypageHistory)
 * renders an `ec-orderMail` block per `Order.MailHistories` row in
 * EC-CUBE's `Mypage/history.twig` (the "メール配信履歴一覧" panel).
 *
 * Maps onto `dtb_mail_history` columns: send_date, mail_subject,
 * mail_body. `mail_html_body` is intentionally omitted — the storefront
 * history screen renders only the plain-text body (`MailHistory.mail_body`).
 */
final readonly class OrderHistoryMailEntity implements \Ray\MediaQuery\ToScalarInterface
{
    use MediaQueryJsonEntityTrait;

    public function __construct(
        public string $sendDate,
        public string $mailSubject,
        public string $mailBody,
    ) {
    }
}
