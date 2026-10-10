<?php
declare(strict_types=1);

namespace Softcode\Gls\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Softcode\Gls\Model\AutoShipment;
use Softcode\Gls\Model\Config;
use Softcode\Gls\Model\Label\LabelService;

/**
 * Creates the GLS label(s) for an order from the admin order view (admin URLs carry
 * the secret key, which protects this GET action against CSRF).
 */
class CreateLabel extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Softcode_Gls::gls_label';

    public function __construct(
        Context $context,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly LabelService $labelService,
        private readonly AutoShipment $autoShipment,
        private readonly Config $config
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');
        $params = ['order_id' => $orderId];

        try {
            $order = $this->orderRepository->get($orderId);
            $result = $this->labelService->createForOrder($order);
            $params['gls_download'] = 1;

            if ($result['errors']) {
                $this->messageManager->addWarningMessage(__(
                    'Created %1 of %2 GLS labels. Problems: %3',
                    $result['count'],
                    $result['parcels'],
                    implode(' | ', $result['errors'])
                ));
            } else {
                $this->messageManager->addSuccessMessage(__(
                    'GLS label(s) created for %1 parcel(s), %2 kg. Consignment: %3',
                    $result['count'],
                    $result['weight'],
                    implode(', ', $result['tracking'])
                ));
            }

            // Already invoiced: the invoice observer ran before there was a label, so ship now.
            if ($this->config->isShipOnInvoice() && (float)$order->getTotalInvoiced() > 0
                && $this->autoShipment->createFor($this->orderRepository->get($orderId))) {
                $this->messageManager->addSuccessMessage(__('The order was shipped and the customer was notified.'));
            }
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__('GLS label error: %1', $e->getMessage()));
        }

        return $this->resultRedirectFactory->create()->setPath('sales/order/view', $params);
    }
}
