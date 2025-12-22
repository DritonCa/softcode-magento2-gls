<?php
namespace Softcode\Gls\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Softcode\Gls\Model\Config;

class Methods extends Action
{
    public function __construct(
        Context $context,
        private JsonFactory $jsonFactory,
        private Config $config
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->jsonFactory->create()->setData([
            'success' => true,
            'methods' => $this->config->getDeliveryMethods()
        ]);
    }
}
