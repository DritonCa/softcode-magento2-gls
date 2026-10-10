<?php
declare(strict_types=1);

namespace Softcode\Gls\Plugin\Adminhtml;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Block\Adminhtml\Order\View;
use Softcode\Gls\Model\Config;
use Softcode\Gls\Model\Label\LabelService;

/**
 * Adds "Create GLS label" / "Download GLS label" buttons to the admin order view.
 */
class OrderViewPlugin
{
    public function __construct(
        private readonly Config $config,
        private readonly LabelService $labelService,
        private readonly AuthorizationInterface $authorization
    ) {
    }

    public function beforeSetLayout(View $subject, LayoutInterface $layout): array
    {
        $order = $subject->getOrder();
        if (!$order || !$this->config->isEnabled() || !$this->authorization->isAllowed('Softcode_Gls::gls_label')) {
            return [$layout];
        }

        $hasLabel = (string)$order->getData('gls_label_pdf') !== '';
        if (!$hasLabel && !$this->labelService->canCreateLabel($order)) {
            return [$layout];
        }

        $createUrl = $subject->getUrl('softcode_gls/order/createLabel', ['order_id' => $order->getId()]);
        if ($hasLabel) {
            $count = max(1, (int)$order->getData('gls_label_count'));
            $subject->addButton('gls_download_label', [
                'label' => $count > 1 ? __('Download GLS Labels (%1)', $count) : __('Download GLS Label'),
                'class' => 'action-secondary',
                'onclick' => sprintf(
                    "setLocation('%s')",
                    $subject->getUrl('softcode_gls/order/downloadLabel', ['order_id' => $order->getId()])
                ),
            ], 0, 20);
            $subject->addButton('gls_recreate_label', [
                'label' => __('Recreate GLS Label'),
                'class' => 'action-secondary',
                'onclick' => sprintf(
                    "confirmSetLocation('%s', '%s')",
                    $subject->escapeJs((string)__('A new label replaces the current one. Continue?')),
                    $createUrl
                ),
            ], 0, 21);
        } else {
            $subject->addButton('gls_create_label', [
                'label' => __('Create GLS Label'),
                'class' => 'action-secondary',
                'onclick' => sprintf("setLocation('%s')", $createUrl),
            ], 0, 20);
        }

        return [$layout];
    }
}
