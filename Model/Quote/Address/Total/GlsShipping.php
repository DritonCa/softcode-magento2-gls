<?php
declare(strict_types=1);

namespace Softcode\Gls\Model\Quote\Address\Total;

use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Softcode\Gls\Model\Config;
use Softcode\Gls\Model\Shippability;

/**
 * Sets the shipping amount from the GLS delivery method chosen on the quote.
 */
class GlsShipping extends AbstractTotal
{
    public function __construct(
        private readonly Config $config,
        private readonly Shippability $shippability
    ) {
        // Overrides the native shipping total.
        $this->setCode('shipping');
    }

    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);

        if (!$shippingAssignment->getItems()) {
            return $this;
        }
        $address = $shippingAssignment->getShipping()->getAddress();

        // Nothing to ship: never let a stale gls_method add a delivery charge.
        if (!$this->shippability->itemsNeedShipping($quote->getAllVisibleItems())) {
            $this->apply($total, $address, 0.0);
            return $this;
        }

        $method = (string)$quote->getData('gls_method');
        if ($method === '') {
            return $this;
        }

        // null means the method is not enabled: never fall through to free shipping.
        $price = $this->config->getMethodPrice($method);
        if ($price !== null) {
            $this->apply($total, $address, $price);
        }
        return $this;
    }

    public function fetch(Quote $quote, Total $total)
    {
        return [
            'code' => 'shipping',
            'title' => __('Shipping'),
            'value' => $total->getShippingAmount(),
        ];
    }

    private function apply(Total $total, $address, float $amount): void
    {
        $total->setShippingAmount($amount);
        $total->setBaseShippingAmount($amount);
        $address->setShippingAmount($amount);
        $address->setBaseShippingAmount($amount);
    }
}
