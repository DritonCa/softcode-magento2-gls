<?php
declare(strict_types=1);

namespace Softcode\Gls\Model\Label;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Sales\Api\Data\OrderInterface;
use Softcode\Gls\Api\RecipientResolverInterface;
use Softcode\Gls\Model\Config;
use Softcode\Gls\Model\Shippability;

/**
 * Creates the GLS label(s) for an order: one label per parcel, stored under
 * media/softcode/gls/labels, plus a combined PDF for printing.
 */
class LabelService
{
    public const LABEL_DIR = 'softcode/gls/labels';

    public function __construct(
        private readonly Config $config,
        private readonly Shippability $shippability,
        private readonly RecipientResolverInterface $recipientResolver,
        private readonly ShipmentRequestBuilder $requestBuilder,
        private readonly ParcelSplitter $parcelSplitter,
        private readonly LabelClient $client,
        private readonly PdfMerger $pdfMerger,
        private readonly Filesystem $filesystem,
        private readonly ResourceConnection $resource
    ) {
    }

    public function canCreateLabel(OrderInterface $order): bool
    {
        return $this->shippability->itemsNeedShipping($order->getAllVisibleItems());
    }

    /**
     * @return array{count: int, parcels: int, tracking: string[], errors: string[], weight: float}
     * @throws LocalizedException when no label at all could be created
     */
    public function createForOrder(OrderInterface $order): array
    {
        $items = $this->shippability->filterShippable($order->getAllVisibleItems());
        if ($items === []) {
            throw new LocalizedException(__('This order has no items that need shipping.'));
        }

        $totalWeight = 0.0;
        foreach ($items as $item) {
            $totalWeight += (float)$item->getRowWeight();
        }
        $parcels = $this->parcelSplitter->split($totalWeight, $this->config->getMaxParcelWeight());

        $credentials = [
            'username' => $this->config->getUsername(),
            'password' => $this->config->getPassword(),
            'customer_id' => $this->config->getCustomerId(),
            'contact_id' => $this->config->getContactId(),
        ];
        if ($credentials['username'] === '' || $credentials['customer_id'] === '') {
            throw new LocalizedException(__('GLS credentials are not configured.'));
        }

        $recipient = $this->recipientResolver->resolve($order);
        $shopId = (string)$order->getData('gls_method') === 'gls_shop' ? (string)$order->getData('gls_shop_id') : '';

        $pdfs = [];
        $tracking = [];
        $errors = [];
        foreach ($parcels as $i => $weight) {
            try {
                $xml = $this->requestBuilder->build(
                    $credentials,
                    $recipient,
                    $weight,
                    $shopId,
                    (string)$order->getIncrementId()
                );
                $result = $this->client->createShipment($xml);
                $pdfs[] = $result['pdf'];
                $tracking[] = $result['consignment_id'];
            } catch (\Throwable $e) {
                $errors[] = (string)__('Parcel %1/%2: %3', $i + 1, count($parcels), $e->getMessage());
            }
        }

        if ($pdfs === []) {
            throw new LocalizedException(__('No GLS labels were created. %1', implode(' | ', $errors)));
        }

        $this->store($order, $pdfs, $tracking);

        return [
            'count' => count($pdfs),
            'parcels' => count($parcels),
            'tracking' => $tracking,
            'errors' => $errors,
            'weight' => round($totalWeight, 1),
        ];
    }

    /**
     * @param string[] $pdfs
     * @param string[] $tracking
     */
    private function store(OrderInterface $order, array $pdfs, array $tracking): void
    {
        $orderId = (int)$order->getEntityId();
        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $media->create(self::LABEL_DIR);

        foreach ($pdfs as $i => $pdf) {
            $media->writeFile(self::parcelPath($orderId, $i + 1), $pdf);
        }
        $merged = $this->pdfMerger->merge($pdfs);
        $combined = self::LABEL_DIR . '/' . $orderId . '.pdf';
        $media->writeFile($combined, $merged ?? $pdfs[0]);

        $data = [
            'gls_label_pdf' => $combined,
            'gls_label_count' => count($pdfs),
            'gls_label_merged' => $merged !== null ? 1 : 0,
            'gls_tracking_number' => implode(', ', $tracking),
            'gls_tracking_url' => $this->config->getTrackingUrl($tracking[0]),
        ];

        // Written directly so creating a label does not run every order-save observer
        // (ERP exports, emails ...) again.
        $connection = $this->resource->getConnection();
        $connection->update(
            $this->resource->getTableName('sales_order'),
            $data,
            ['entity_id = ?' => $orderId]
        );
        foreach ($data as $key => $value) {
            $order->setData($key, $value);
        }
    }

    public static function parcelPath(int $orderId, int $parcel): string
    {
        return self::LABEL_DIR . '/' . $orderId . '-' . $parcel . '.pdf';
    }
}
