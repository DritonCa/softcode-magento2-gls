<?php
declare(strict_types=1);

namespace Softcode\Gls\Controller\Index;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;
use Softcode\Gls\Model\Config;

/**
 * Saves the chosen GLS delivery method (and parcel-shop id) on the quote.
 *
 * The method is validated server-side against the enabled, configured methods,
 * so a forged request cannot set an unknown method (which would otherwise result
 * in zero shipping cost).
 */
class Save implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly JsonFactory $resultJsonFactory,
        private readonly CheckoutSession $checkoutSession,
        private readonly CartRepositoryInterface $quoteRepository,
        private readonly Config $config,
        private readonly RequestInterface $request,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly LoggerInterface $logger
    ) {
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $this->formKeyValidator->validate($request);
    }

    public function execute(): ResultInterface
    {
        $result = $this->resultJsonFactory->create();

        try {
            $method = (string) $this->request->getParam('method', '');
            $shopId = (string) $this->request->getParam('shop_id', '');

            if (!$this->config->isAllowedMethod($method)) {
                throw new LocalizedException(__('Please choose a valid GLS delivery method.'));
            }
            if ($method === 'gls_shop' && $shopId === '') {
                throw new LocalizedException(__('Please select a GLS parcel shop.'));
            }

            $quote = $this->checkoutSession->getQuote();
            if (!$quote->getId()) {
                throw new LocalizedException(__('There is no active cart.'));
            }

            $quote->setData('gls_method', $method);
            $quote->setData('gls_shop_id', $method === 'gls_shop' ? $shopId : null);

            // Collect rates first so the shipping method exists, then select it.
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true);
            $quote->collectTotals();
            $shippingAddress->setShippingMethod('softcode_gls_gls');

            $this->quoteRepository->save($quote);

            return $result->setData(['success' => true]);
        } catch (LocalizedException $e) {
            return $result->setData(['success' => false, 'error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->logger->error('GLS save failed', ['exception' => $e]);
            return $result->setData(['success' => false, 'error' => __('The delivery method could not be saved.')]);
        }
    }
}
