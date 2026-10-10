<?php
declare(strict_types=1);

namespace Softcode\Gls\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order;

/**
 * "GLS delivery" box on the admin order view: method, parcel shop, tracking and labels.
 */
class GlsInfo extends Template
{
    private const METHOD_LABELS = [
        'gls_shop' => 'GLS Parcel Shop',
        'gls_business' => 'GLS Business',
        'gls_home' => 'GLS Home',
    ];

    public function __construct(
        Context $context,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getOrder(): ?Order
    {
        $order = $this->registry->registry('current_order');
        return $order instanceof Order ? $order : null;
    }

    public function getField(string $key): string
    {
        return trim((string)$this->getOrder()?->getData($key));
    }

    public function getMethodLabel(): string
    {
        $method = $this->getField('gls_method');
        return isset(self::METHOD_LABELS[$method]) ? (string)__(self::METHOD_LABELS[$method]) : $method;
    }

    public function hasLabel(): bool
    {
        return $this->getField('gls_label_pdf') !== '';
    }

    public function getLabelCount(): int
    {
        return max(1, (int)$this->getField('gls_label_count'));
    }

    public function isLabelMerged(): bool
    {
        return (bool)$this->getField('gls_label_merged');
    }

    /**
     * True right after a label was created, so the page starts the download itself.
     */
    public function shouldDownloadNow(): bool
    {
        return $this->hasLabel() && (int)$this->getRequest()->getParam('gls_download') === 1;
    }

    public function getDownloadUrl(int $parcel = 0): string
    {
        $params = ['order_id' => (int)$this->getOrder()?->getId()];
        if ($parcel > 0) {
            $params['parcel'] = $parcel;
        }
        return $this->getUrl('softcode_gls/order/downloadLabel', $params);
    }
}
