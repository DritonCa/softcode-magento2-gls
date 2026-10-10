<?php
declare(strict_types=1);

namespace Softcode\Gls\Model;

use Softcode\Gls\Api\ItemShippingRuleInterface;

/**
 * Single answer to "does this item / cart / order need physical delivery?".
 *
 * Used by the carrier (which methods to offer), the shipping total (whether to
 * charge), label creation (which items go in the parcel) and auto-shipment. An item
 * ships unless one of the configured rules excludes it.
 */
class Shippability
{
    /**
     * @param ItemShippingRuleInterface[] $rules
     */
    public function __construct(
        private readonly array $rules = []
    ) {
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Sales\Model\Order\Item|object $item
     */
    public function itemNeedsShipping($item): bool
    {
        // Child items of configurables/bundles follow their parent.
        if (method_exists($item, 'getParentItem') && $item->getParentItem()) {
            return false;
        }
        foreach ($this->rules as $rule) {
            if ($rule instanceof ItemShippingRuleInterface && $rule->excludes($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param iterable<object> $items
     */
    public function itemsNeedShipping(iterable $items): bool
    {
        foreach ($items as $item) {
            if ($this->itemNeedsShipping($item)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param iterable<object> $items
     * @return object[] the items that go into the parcel
     */
    public function filterShippable(iterable $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if ($this->itemNeedsShipping($item)) {
                $result[] = $item;
            }
        }
        return $result;
    }
}
