<?php
declare(strict_types=1);

namespace Softcode\Gls\Model\Label;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\CurlFactory;
use Softcode\Gls\Model\Config;

/**
 * Calls GLS CreateShipment and returns the label PDF and consignment id.
 */
class LabelClient
{
    public function __construct(
        private readonly CurlFactory $curlFactory,
        private readonly Config $config
    ) {
    }

    /**
     * @return array{pdf: string, consignment_id: string}
     * @throws LocalizedException
     */
    public function createShipment(string $requestXml): array
    {
        $curl = $this->curlFactory->create();
        $curl->setTimeout(30);
        $curl->addHeader('Content-Type', 'text/xml; charset="utf-8"');
        $curl->addHeader('Accept', 'text/xml');
        $curl->post($this->config->getLabelApiUrl(), $requestXml);

        $status = $curl->getStatus();
        $body = (string)$curl->getBody();
        if ($status >= 400 || $body === '') {
            throw new LocalizedException(__('GLS returned HTTP %1: %2', $status, mb_substr($body, 0, 300)));
        }

        return self::parseResponse($body);
    }

    /**
     * @return array{pdf: string, consignment_id: string}
     * @throws LocalizedException
     */
    public static function parseResponse(string $body): array
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_use_internal_errors($previous);

        $pdf = $xml !== false ? base64_decode((string)($xml->PDF ?? ''), true) : false;
        $consignmentId = $xml !== false ? trim((string)($xml->ConsignmentId ?? '')) : '';

        if ($pdf === false || $pdf === '' || $consignmentId === '') {
            throw new LocalizedException(__('Unexpected GLS response: %1', mb_substr($body, 0, 300)));
        }
        return ['pdf' => $pdf, 'consignment_id' => $consignmentId];
    }
}
