<?php
declare(strict_types=1);

namespace Softcode\Gls\Model\ShippingRule;

use Softcode\Gls\Api\ItemShippingRuleInterface;

/**
 * Virtual and downloadable products never ship.
 */
class ProductTypeRule implements ItemShippingRuleInterface
{
    /**
     * @param string[] $nonShippableTypes
     */
    public function __construct(
        private readonly array $nonShippableTypes = ['virtual', 'downloadable']
    ) {
    }

    public function excludes($item): bool
    {
        return in_array((string)$item->getProductType(), $this->nonShippableTypes, true);
    }
}
