<?php
namespace Softcode\Gls\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Rate\ResultFactory;

class Gls extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'softcode_gls';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        private ResultFactory $rateResultFactory,
        private MethodFactory $rateMethodFactory,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->rateResultFactory->create();

        $method = $this->rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle('GLS');
        $method->setMethod('gls');
        $method->setMethodTitle('GLS Delivery');

        // Price is handled by your total, not here
        $method->setPrice(0);
        $method->setCost(0);

        $result->append($method);

        return $result;
    }

    public function getAllowedMethods()
    {
        return ['gls' => 'GLS'];
    }
}
