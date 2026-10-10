<?php
declare(strict_types=1);

namespace Softcode\Gls\Model;

use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\ShipmentFactory;
use Psr\Log\LoggerInterface;

/**
 * Creates the Magento shipment (with GLS tracking numbers) for an order that has a
 * GLS label, and sends Magento's shipment email with the tracking link.
 *
 * Called both when an order with a label is invoiced and when a label is created on
 * an order that is already invoiced, so the order never gets stuck in "processing".
 */
class AutoShipment
{
    public function __construct(
        private readonly Shippability $shippability,
        private readonly ShipmentFactory $shipmentFactory,
        private readonly TransactionFactory $transactionFactory,
        private readonly ShipmentSender $shipmentSender,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return bool true when a shipment was created
     */
    public function createFor(OrderInterface $order): bool
    {
        if (!$order instanceof Order) {
            return false;
        }
        $trackingNumbers = array_values(array_filter(array_map(
            'trim',
            explode(',', (string)$order->getData('gls_tracking_number'))
        )));
        if ($trackingNumbers === [] || !$order->canShip() || $order->getShipmentsCollection()->getSize() > 0) {
            return false;
        }

        $qtys = [];
        foreach ($order->getAllItems() as $item) {
            $root = $item->getParentItem() ?: $item;
            if (!$this->shippability->itemNeedsShipping($root)) {
                continue;
            }
            $qty = (float)$item->getQtyToShip();
            if ($qty > 0) {
                $qtys[(int)$item->getId()] = $qty;
            }
        }
        if ($qtys === []) {
            return false;
        }

        $tracks = array_map(static fn (string $number): array => [
            'carrier_code' => 'softcode_gls',
            'title' => 'GLS',
            'number' => $number,
        ], $trackingNumbers);

        try {
            $shipment = $this->shipmentFactory->create($order, $qtys, $tracks);
            $shipment->register();
            $order->setIsInProcess(true);
            $this->transactionFactory->create()->addObject($shipment)->addObject($order)->save();
            $this->shipmentSender->send($shipment);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error(
                'GLS auto-shipment failed for order ' . $order->getIncrementId() . ': ' . $e->getMessage()
            );
            return false;
        }
    }
}
