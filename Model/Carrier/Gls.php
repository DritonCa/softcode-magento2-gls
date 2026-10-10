<?php
declare(strict_types=1);

namespace Softcode\Gls\Model\Carrier;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Psr\Log\LoggerInterface;
use Softcode\Gls\Model\Config;
use Softcode\Gls\Model\Shippability;

/**
 * GLS carrier.
 *
 * Offers one rate, `softcode_gls_gls`; the price comes from the delivery method the
 * customer picked (see GlsShipping total). A cart where nothing needs shipping gets a
 * free `softcode_gls_none` rate instead, so it can be ordered without a delivery step.
 */
class Gls extends AbstractCarrier implements CarrierInterface
{
    public const CODE = 'softcode_gls';
    public const METHOD_DELIVERY = 'gls';
    public const METHOD_NONE = 'none';

    protected $_code = self::CODE;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        private readonly ResultFactory $rateResultFactory,
        private readonly MethodFactory $rateMethodFactory,
        private readonly StatusFactory $trackStatusFactory,
        private readonly Shippability $shippability,
        private readonly Config $config,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active') || !$this->config->isEnabled()) {
            return false;
        }

        $needsShipping = $this->shippability->itemsNeedShipping($request->getAllItems() ?: []);
        $method = $this->rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle((string)$this->getConfigData('title'));
        $method->setMethod($needsShipping ? self::METHOD_DELIVERY : self::METHOD_NONE);
        $method->setMethodTitle($needsShipping ? __('GLS Delivery') : __('No delivery required'));
        // The price is applied by the GlsShipping total from the chosen delivery method.
        $method->setPrice(0);
        $method->setCost(0);

        $result = $this->rateResultFactory->create();
        $result->append($method);
        return $result;
    }

    public function getAllowedMethods()
    {
        return [self::METHOD_DELIVERY => 'GLS', self::METHOD_NONE => (string)__('No delivery required')];
    }

    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * Makes the tracking number in Magento's shipment email and "My orders" a link to GLS.
     *
     * @param string $trackingNumber
     * @return \Magento\Shipping\Model\Tracking\Result\Status
     */
    public function getTrackingInfo($trackingNumber)
    {
        $status = $this->trackStatusFactory->create();
        $status->setCarrier($this->_code);
        $status->setCarrierTitle((string)$this->getConfigData('title') ?: 'GLS');
        $status->setTracking($trackingNumber);
        $status->setUrl($this->config->getTrackingUrl((string)$trackingNumber));
        return $status;
    }
}
