<?php
declare(strict_types=1);

namespace Softcode\Gls\Controller\Index;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Softcode\Gls\Model\Config;
use Softcode\Gls\Model\Shippability;

/**
 * Lists the enabled GLS delivery methods for the checkout. `needs_shipping` is false
 * when nothing in the cart ships, so the checkout can hide the delivery step.
 */
class Methods implements HttpGetActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly Config $config,
        private readonly CheckoutSession $checkoutSession,
        private readonly Shippability $shippability
    ) {
    }

    public function execute(): ResultInterface
    {
        $quote = $this->checkoutSession->getQuote();
        $needsShipping = !$quote->getId() || $this->shippability->itemsNeedShipping($quote->getAllVisibleItems());

        return $this->jsonFactory->create()->setData([
            'success' => true,
            'needs_shipping' => $needsShipping,
            'methods' => $needsShipping ? $this->config->getDeliveryMethods() : [],
        ]);
    }
}
