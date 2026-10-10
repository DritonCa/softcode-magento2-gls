<?php
declare(strict_types=1);

namespace Softcode\Gls\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Typed access to the module's admin configuration (Stores > Configuration >
 * Sales > Shipping Methods > GLS).
 */
class Config
{
    private const XML_PATH_BASE = 'shipping/gls/';

    public const DEFAULT_LABEL_API_URL = 'https://api.gls.dk/ws/DK/V1/CreateShipment';
    public const DEFAULT_TRACKING_URL = 'https://gls-group.eu/DK/da/find-pakke?match={number}';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly ?EncryptorInterface $encryptor = null
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->isFlag('enabled');
    }

    public function getUsername(): string
    {
        return $this->getValue('username');
    }

    public function getPassword(): string
    {
        $stored = $this->getValue('password');
        if ($stored === '' || $this->encryptor === null) {
            return $stored;
        }
        // Passwords saved before 2.0 were stored unencrypted; decrypting those yields ''.
        $decrypted = (string)$this->encryptor->decrypt($stored);
        return $decrypted !== '' ? $decrypted : $stored;
    }

    public function getCustomerId(): string
    {
        return $this->getValue('customer_id');
    }

    public function getContactId(): ?string
    {
        $value = $this->getValue('contact_id');
        return $value !== '' ? $value : null;
    }

    /**
     * Enabled delivery methods in a stable order.
     *
     * @return array<int, array{code: string, label: string, price: float}>
     */
    public function getDeliveryMethods(): array
    {
        if (!$this->isEnabled()) {
            return [];
        }

        $methods = [];
        foreach ([
            'business' => 'GLS – Ship to Business',
            'home' => 'GLS – Ship to Home',
            'shop' => 'GLS – Ship to Shop',
        ] as $key => $label) {
            if ($this->isFlag($key . '_enabled')) {
                $methods[] = [
                    'code' => 'gls_' . $key,
                    'label' => $label,
                    'price' => (float)$this->getValue($key . '_price'),
                ];
            }
        }
        return $methods;
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
                return $method['price'];
            }
        }
        return null;
    }

    /**
     * SKUs that never ship (digital products, services, gift cards sold as simple products ...).
     *
     * @return string[]
     */
    public function getExcludedSkus(): array
    {
        $raw = $this->getValue('excluded_skus');
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $raw) ?: [])));
    }

    /**
     * Max weight (kg) per parcel; heavier orders are split into several labels. 0 disables splitting.
     */
    public function getMaxParcelWeight(): float
    {
        return max(0.0, (float)$this->getValue('max_parcel_weight'));
    }

    /**
     * When enabled, invoicing an order that has a GLS label creates the shipment and
     * sends Magento's shipment email with the tracking link.
     */
    public function isShipOnInvoice(): bool
    {
        return $this->isFlag('ship_on_invoice');
    }

    public function getLabelApiUrl(): string
    {
        return $this->getValue('label_api_url') ?: self::DEFAULT_LABEL_API_URL;
    }

    public function getTrackingUrl(string $trackingNumber): string
    {
        $template = $this->getValue('tracking_url') ?: self::DEFAULT_TRACKING_URL;
        return str_replace('{number}', rawurlencode($trackingNumber), $template);
    }

    private function isFlag(string $key): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_BASE . $key, ScopeInterface::SCOPE_WEBSITE);
    }

    private function getValue(string $key): string
    {
        return trim((string)$this->scopeConfig->getValue(self::XML_PATH_BASE . $key, ScopeInterface::SCOPE_WEBSITE));
    }
}
