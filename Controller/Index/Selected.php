<?php
declare(strict_types=1);

namespace Softcode\Gls\Controller\Index;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Returns the GLS choice stored on the current quote, so the checkout can restore it.
 */
class Selected implements HttpGetActionInterface
{
    public function __construct(
        private readonly JsonFactory $jsonFactory,
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    public function execute(): ResultInterface
    {
        $quote = $this->checkoutSession->getQuote();

        return $this->jsonFactory->create()->setData([
            'success' => true,
            'method' => $quote->getData('gls_method'),
            'shop_id' => $quote->getData('gls_shop_id'),
            'shop_name' => $quote->getData('gls_shop_name'),
            'shop_street' => $quote->getData('gls_shop_address'),
            'shop_postcode' => $quote->getData('gls_shop_postcode'),
            'shop_city' => $quote->getData('gls_shop_city'),
        ]);
    }
}
