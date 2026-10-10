<?php
declare(strict_types=1);

namespace Softcode\Gls\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Softcode\Gls\Model\AutoShipment;
use Softcode\Gls\Model\Config;

/**
 * Ships an order with a GLS label as soon as it is invoiced.
 *
 * Set `gls_skip_auto_shipment` on the invoice (e.g. for invoices created for orders
 * that were shipped some other way) to opt out for that invoice.
 */
class CreateShipmentOnInvoice implements ObserverInterface
{
    public const SKIP_FLAG = 'gls_skip_auto_shipment';

    public function __construct(
        private readonly Config $config,
        private readonly AutoShipment $autoShipment
    ) {
    }

    public function execute(Observer $observer): void
    {
        $invoice = $observer->getEvent()->getInvoice();
        if (!$invoice || !$this->config->isShipOnInvoice() || $invoice->getData(self::SKIP_FLAG)) {
            return;
        }
        $order = $invoice->getOrder();
        if ($order) {
            $this->autoShipment->createFor($order);
        }
    }
}
