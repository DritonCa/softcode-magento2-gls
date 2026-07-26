<?php
namespace Softcode\Gls\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Softcode\Gls\Model\Config as GlsConfig;

/**
 * Exposes the GLS configuration to templates through dependency injection,
 * so templates never touch the ObjectManager directly.
 */
class Config implements ArgumentInterface
{
    public function __construct(
        private readonly GlsConfig $config
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }
}
