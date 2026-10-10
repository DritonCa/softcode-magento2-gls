<?php
declare(strict_types=1);

namespace Softcode\Gls\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Sales\Api\OrderRepositoryInterface;
use Softcode\Gls\Model\Label\LabelService;

/**
 * Downloads one parcel label (parcel=N) or all labels (combined PDF, or a ZIP when
 * the PDFs could not be merged).
 */
class DownloadLabel extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Softcode_Gls::gls_label';

    public function __construct(
        Context $context,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly FileFactory $fileFactory,
        private readonly Filesystem $filesystem
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $orderId = (int)$this->getRequest()->getParam('order_id');
        $parcel = (int)$this->getRequest()->getParam('parcel');

        try {
            $order = $this->orderRepository->get($orderId);
            $combined = (string)$order->getData('gls_label_pdf');
            if ($combined === '') {
                throw new LocalizedException(__('No GLS label found for this order.'));
            }
            $count = max(1, (int)$order->getData('gls_label_count'));
            $media = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);

            if ($parcel > 0) {
                return $this->sendPdf($media->getAbsolutePath(LabelService::parcelPath($orderId, $parcel)), "gls_label_{$orderId}_{$parcel}.pdf");
            }
            if ($count === 1 || (bool)$order->getData('gls_label_merged')) {
                return $this->sendPdf($media->getAbsolutePath($combined), "gls_label_{$orderId}.pdf");
            }
            return $this->sendZip($orderId, $count);
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $this->resultRedirectFactory->create()->setPath('sales/order/view', ['order_id' => $orderId]);
        }
    }

    private function sendPdf(string $absolutePath, string $fileName)
    {
        if (!is_file($absolutePath)) {
            throw new LocalizedException(__('The label file is missing on disk.'));
        }
        return $this->fileFactory->create(
            $fileName,
            ['type' => 'filename', 'value' => $absolutePath],
            DirectoryList::MEDIA,
            'application/pdf'
        );
    }

    private function sendZip(int $orderId, int $count)
    {
        $media = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $var = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $var->create('softcode/gls');
        $zipRel = 'softcode/gls/labels_' . $orderId . '_' . uniqid() . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($var->getAbsolutePath($zipRel), \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new LocalizedException(__('Could not build the label archive.'));
        }
        $added = 0;
        for ($i = 1; $i <= $count; $i++) {
            $path = LabelService::parcelPath($orderId, $i);
            if ($media->isFile($path)) {
                $zip->addFile($media->getAbsolutePath($path), 'label-' . $i . '.pdf');
                $added++;
            }
        }
        $zip->close();
        if ($added === 0) {
            $var->delete($zipRel);
            throw new LocalizedException(__('No label files found on disk.'));
        }

        return $this->fileFactory->create(
            "gls_labels_{$orderId}.zip",
            ['type' => 'filename', 'value' => $zipRel, 'rm' => true],
            DirectoryList::VAR_DIR,
            'application/zip'
        );
    }
}
