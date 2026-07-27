<?php
namespace Softcode\Gls\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_BASE = 'shipping/gls/';

    protected ScopeConfigInterface $scopeConfig;


    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->isSetFlag(
            self::XML_PATH_BASE . 'enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    public function getUsername(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_BASE . 'username',
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    public function getPassword(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_BASE . 'password',
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    public function getCustomerId(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_BASE . 'customer_id',
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    public function getContactId(): ?string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_BASE . 'contact_id',
            ScopeInterface::SCOPE_WEBSITE
        );

        return $value !== '' ? (string)$value : null;
    }

    public function getDeliveryMethods(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $methods = [];

        if ($this->isFlag('business_enabled')) {
            $methods[] = [
                'code'  => 'gls_business',
                'label' => 'GLS – Ship to Business',
                'price' => (float)$this->getValue('business_price')
            ];
        }

        if ($this->isFlag('home_enabled')) {
            $methods[] = [
                'code'  => 'gls_home',
                'label' => 'GLS – Ship to Home',
                'price' => (float)$this->getValue('home_price')
            ];
        }

        if ($this->isFlag('shop_enabled')) {
            $methods[] = [
                'code'  => 'gls_shop',
                'label' => 'GLS – Ship to Shop',
                'price' => (float)$this->getValue('shop_price')
            ];
        }

        return $methods;
    }

    private function isFlag(string $key): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_BASE.$key, ScopeInterface::SCOPE_WEBSITE);
    }

    private function getValue(string $key): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_BASE.$key, ScopeInterface::SCOPE_WEBSITE);
    }

    /**
     * @return string[] enabled GLS method codes
     */
    public function getMethodCodes(): array
    {
        return array_column($this->getDeliveryMethods(), 'code');
    }

    public function isAllowedMethod(string $code): bool
    {
        return in_array($code, $this->getMethodCodes(), true);
    }

    /**
     * Configured price for an enabled method, or null if the method is not allowed.
     */
    public function getMethodPrice(string $code): ?float
    {
        foreach ($this->getDeliveryMethods() as $method) {
            if ($method['code'] === $code) {
                return (float) $method['price'];
            }
        }
        return null;
    }
}
