<?php
declare(strict_types=1);

namespace Softcode\Gls\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

/**
 * Looks up nearby GLS parcel shops for a postcode via the GLS shop-finder API.
 *
 * @SuppressWarnings(PHPMD.CamelCaseClassName)
 */
class getGlsList implements HttpGetActionInterface
{
    private const SHOP_FINDER_URL =
        'https://www.gls.dk/webservices_v4/wsShopFinder.asmx/GetParcelShopDropPoint';

    public function __construct(
        private readonly JsonFactory $resultJsonFactory,
        private readonly RequestInterface $request,
        private readonly Curl $curl,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): ResultInterface
    {
        $result = $this->resultJsonFactory->create();

        $zipcode = trim((string) $this->request->getParam('zipcode', ''));
        $street = trim((string) $this->request->getParam('street', ''));
        $country = strtoupper((string) $this->request->getParam('country', 'DK'));
        $amount = max(1, min(50, (int) $this->request->getParam('amount', 20)));

        if ($zipcode === '' || $street === '') {
            return $result->setData(['success' => false, 'error' => __('Street and postcode are required.')]);
        }

        $url = self::SHOP_FINDER_URL
            . '?street=' . urlencode($street)
            . '&zipcode=' . urlencode($zipcode)
            . '&countryIso3166A2=' . urlencode($country)
            . '&Amount=' . $amount;

        try {
            $this->curl->setTimeout(5);
            $this->curl->get($url);

            if ($this->curl->getStatus() !== 200) {
                throw new \RuntimeException('GLS API returned status ' . $this->curl->getStatus());
            }

            $xml = simplexml_load_string($this->curl->getBody());
            if ($xml === false) {
                throw new \RuntimeException('Invalid GLS XML response');
            }
            $xml->registerXPathNamespace('gls', 'http://gls.dk/webservices/');

            $shops = [];
            foreach ($xml->xpath('//gls:PakkeshopData') ?: [] as $node) {
                $shops[] = [
                    'id' => (string) $node->Number,
                    'name' => (string) $node->CompanyName,
                    'street' => (string) $node->Streetname,
                    'zipcode' => (string) $node->ZipCode,
                    'city' => (string) $node->CityName,
                    'distance' => (int) $node->DistanceMetersAsTheCrowFlies,
                ];
            }

            return $result->setData(['success' => true, 'shops' => $shops]);
        } catch (\Throwable $e) {
            $this->logger->error('GLS shop lookup failed', ['exception' => $e]);
            return $result->setData(['success' => false, 'error' => __('Parcel shops could not be loaded right now.')]);
        }
    }
}
