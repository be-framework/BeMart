<?php

declare(strict_types=1);

namespace MyVendor\BeMart\Be\Semantic;

use Be\Framework\Attribute\Validate;

/**
 * TrustedHosts — 信頼するホストの正規表現（doUpdateSecurity）。改行区切りのホスト/IP/CIDR
 * 一覧（trustedHosts は正規表現）。EC-CUBE 互換の自由書式のため
 * ontology 上の存在確認に留め、適用は境界サービスが担う。
 */
final class TrustedHosts
{
    #[Validate]
    public function validate(string $trustedHosts): void
    {
    }
}
