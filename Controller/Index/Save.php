<?php
namespace Softcode\Gls\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;

class Save extends Action
{
    public function __construct(
        Context $context,
        private JsonFactory $jsonFactory,
        private CheckoutSession $checkoutSession,
        private CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $method = (string)$this->getRequest()->getParam('method');
            $shopId = (string)$this->getRequest()->getParam('shop_id');

            if (!$method) {
                throw new \Exception('Missing GLS delivery method');
            }

            $quote = $this->checkoutSession->getQuote();
            if (!$quote->getId()) {
                throw new \Exception('No active quote');
            }

            /* =========================
               SAVE GLS META
            ========================== */
            $quote->setData('gls_method', $method);
            $quote->setData(
                'gls_shop_id',
                $method === 'gls_shop' ? $shopId : null
            );

            $shippingAddress = $quote->getShippingAddress();

            /* =========================
               🔑 CRITICAL SEQUENCE
            ========================== */

            // 1. Force rate collection
            $shippingAddress->setCollectShippingRates(true);
            $quote->collectTotals();

            // 2. Now the rate EXISTS → set method
            $shippingAddress->setShippingMethod('softcode_gls_gls');

            // 3. Save
            $this->quoteRepository->save($quote);

            return $result->setData(['success' => true]);

        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'error'   => $e->getMessage()
            ]);
        }
    }
}
