<?php
namespace Softcode\Gls\Model\Quote\Address\Total;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Softcode\Gls\Model\Config;

class GlsShipping extends AbstractTotal
{
    public function __construct(
        private Config $config
    ) {
        // 🔑 IMPORTANT: override native shipping
        $this->setCode('shipping');
    }

    public function collect(
        Quote $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);

        if (!$shippingAssignment->getItems()) {
            return $this;
        }

        $method = (string)$quote->getData('gls_method');
        if (!$method) {
            return $this;
        }

        // Look the price up centrally; null means the method is not an enabled,
        // configured method — never fall through to free shipping in that case.
        $price = $this->config->getMethodPrice($method);
        if ($price === null) {
            return $this;
        }

        // Only override the shipping amounts (a configured price of 0 is a valid free method).
        $total->setShippingAmount($price);
        $total->setBaseShippingAmount($price);

        return $this;
    }

    /**
     * Ensure correct display in totals blocks
     */
    public function fetch(Quote $quote, Total $total)
    {
        return [
            'code'  => 'shipping',
            'title' => __('Shipping'),
            'value' => $total->getShippingAmount()
        ];
    }
}
