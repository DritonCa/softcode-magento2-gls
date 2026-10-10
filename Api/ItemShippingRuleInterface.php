<?php
declare(strict_types=1);

namespace Softcode\Gls\Api;

/**
 * A rule that can take an item out of physical delivery.
 *
 * The module ships with rules for virtual/downloadable products and for SKUs listed
 * in the admin. Projects add their own rules (e.g. "courses never ship") by adding an
 * item to the `rules` argument of {@see \Softcode\Gls\Model\Shippability} in di.xml,
 * so no project-specific logic has to live in this module.
 */
interface ItemShippingRuleInterface
{
    /**
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Sales\Model\Order\Item|object $item
     * @return bool true when this item must NOT be shipped
     */
    public function excludes($item): bool;
}
