<?php
declare(strict_types=1);

namespace Softcode\Gls\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Softcode\Gls\Model\Config;
use PHPUnit\Framework\TestCase;

/**
 * Executable specification of the GLS delivery-method rules.
 *
 * Config is the single source of truth for which GLS methods are offered and at
 * what price. These tests pin that behaviour without a running Magento instance
 * by mocking ScopeConfigInterface with a flat path => value map.
 */
class ConfigTest extends TestCase
{
    /**
     * @param array<string, mixed> $settings store-config path => value
     */
    private function config(array $settings): Config
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);

        $scopeConfig->method('isSetFlag')
            ->willReturnCallback(
                static fn (string $path): bool => (bool)($settings[$path] ?? false)
            );

        $scopeConfig->method('getValue')
            ->willReturnCallback(
                static fn (string $path) => $settings[$path] ?? null
            );

        return new Config($scopeConfig);
    }

    public function testNoMethodsWhenCarrierDisabled(): void
    {
        // Even with every sub-method flag on, a disabled carrier offers nothing.
        $config = $this->config([
            'shipping/gls/enabled'          => false,
            'shipping/gls/home_enabled'     => true,
            'shipping/gls/business_enabled' => true,
            'shipping/gls/shop_enabled'     => true,
        ]);

        $this->assertSame([], $config->getDeliveryMethods());
        $this->assertSame([], $config->getMethodCodes());
        $this->assertFalse($config->isAllowedMethod('gls_home'));
        $this->assertNull($config->getMethodPrice('gls_home'));
    }

    public function testOnlyEnabledMethodsAreOffered(): void
    {
        $config = $this->config([
            'shipping/gls/enabled'      => true,
            'shipping/gls/home_enabled' => true,
            'shipping/gls/home_price'   => '49.50',
            // business + shop deliberately left disabled
        ]);

        $this->assertSame(['gls_home'], $config->getMethodCodes());
        $this->assertTrue($config->isAllowedMethod('gls_home'));
        $this->assertFalse($config->isAllowedMethod('gls_business'));
        $this->assertFalse($config->isAllowedMethod('gls_shop'));
        $this->assertSame(49.5, $config->getMethodPrice('gls_home'));
    }

    public function testAllThreeMethodsInStableOrderWithPrices(): void
    {
        $config = $this->config([
            'shipping/gls/enabled'          => true,
            'shipping/gls/business_enabled' => true,
            'shipping/gls/business_price'   => '39',
            'shipping/gls/home_enabled'     => true,
            'shipping/gls/home_price'       => '49',
            'shipping/gls/shop_enabled'     => true,
            'shipping/gls/shop_price'       => '0',
        ]);

        $methods = $config->getDeliveryMethods();

        $this->assertCount(3, $methods);
        $this->assertSame(
            ['gls_business', 'gls_home', 'gls_shop'],
            array_column($methods, 'code')
        );
        $this->assertSame(39.0, $config->getMethodPrice('gls_business'));
        $this->assertSame(49.0, $config->getMethodPrice('gls_home'));
    }

    public function testGetMethodPriceDistinguishesFreeFromInvalid(): void
    {
        // A configured price of 0 means "free shipping" (0.0), which must be
        // distinguishable from "method not allowed" (null). GlsShipping relies on
        // exactly this: null => skip, 0.0 => a valid free rate.
        $config = $this->config([
            'shipping/gls/enabled'      => true,
            'shipping/gls/shop_enabled' => true,
            'shipping/gls/shop_price'   => '0',
        ]);

        $this->assertSame(0.0, $config->getMethodPrice('gls_shop'));
        $this->assertNull($config->getMethodPrice('gls_home'));       // disabled
        $this->assertNull($config->getMethodPrice('gls_nonexistent')); // unknown
    }

    public function testIsAllowedMethodIsStrictAndCaseSensitive(): void
    {
        $config = $this->config([
            'shipping/gls/enabled'      => true,
            'shipping/gls/home_enabled' => true,
            'shipping/gls/home_price'   => '49',
        ]);

        $this->assertTrue($config->isAllowedMethod('gls_home'));
        $this->assertFalse($config->isAllowedMethod('GLS_HOME'));
        $this->assertFalse($config->isAllowedMethod(''));
        $this->assertFalse($config->isAllowedMethod('flatrate'));
    }
}
