<?php
namespace Softcode\Gls\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;

class Selected extends Action
{
    public function __construct(
        Context $context,
        private JsonFactory $jsonFactory,
        private CheckoutSession $checkoutSession
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();

        return $this->jsonFactory->create()->setData([
            'success' => true,
            'method'  => $quote->getData('gls_method'),
            'shop_id' => $quote->getData('gls_shop_id')
        ]);
    }
}
