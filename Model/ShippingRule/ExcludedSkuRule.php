<?php
declare(strict_types=1);

namespace Softcode\Gls\Model\ShippingRule;

use Softcode\Gls\Api\ItemShippingRuleInterface;
use Softcode\Gls\Model\Config;

/**
 * SKUs listed under "Non-shippable SKUs" in the admin never ship.
 */
class ExcludedSkuRule implements ItemShippingRuleInterface
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function excludes($item): bool
    {
        return in_array((string)$item->getSku(), $this->config->getExcludedSkus(), true);
    }
}
