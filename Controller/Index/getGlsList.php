<?php
namespace Softcode\Gls\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;

class getGlsList extends Action
{
    public function __construct(
    Context $context,
    private JsonFactory $jsonFactory,
    private Curl $curl
) {
    parent::__construct($context);
}

    public function execute()
{
    $result = $this->jsonFactory->create();

    $zipcode = trim((string)$this->getRequest()->getParam('zipcode'));
    $street  = trim((string)$this->getRequest()->getParam('street'));
    $country = strtoupper((string)$this->getRequest()->getParam('country', 'DK'));
    $amount  = (int)$this->getRequest()->getParam('amount', 20);

    /* Basic validation */
    if ($zipcode === '' || $street === '') {
        return $result->setData([
            'success' => false,
            'error'   => 'Street and zipcode are required'
        ]);
    }

    $url = 'http://www.gls.dk/webservices_v4/wsShopFinder.asmx/GetParcelShopDropPoint'
        . '?street=' . urlencode($street)
        . '&zipcode=' . urlencode($zipcode)
        . '&countryIso3166A2=' . urlencode($country)
        . '&Amount=' . $amount;

    try {
        /* Use Magento HTTP client */
        $this->curl->setTimeout(5);
        $this->curl->get($url);

        if ($this->curl->getStatus() !== 200) {
            throw new \Exception('GLS API error');
        }

        $xml = simplexml_load_string($this->curl->getBody());
        if (!$xml) {
            throw new \Exception('Invalid GLS XML response');
        }

        $xml->registerXPathNamespace('gls', 'http://gls.dk/webservices/');

        $shops = [];
        foreach ($xml->xpath('//gls:PakkeshopData') as $node) {
            $shops[] = [
                'id'       => (string)$node->Number,
                'name'     => (string)$node->CompanyName,
                'street'   => (string)$node->Streetname,
                'zipcode'  => (string)$node->ZipCode,
                'city'     => (string)$node->CityName,
                'distance' => (int)$node->DistanceMetersAsTheCrowFlies,
            ];
        }

        return $result->setData([
            'success' => true,
            'shops'   => $shops
        ]);

    } catch (\Throwable $e) {
        return $result->setData([
            'success' => false,
            'error'   => $e->getMessage()
        ]);
    }
}
}
