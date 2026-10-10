<?php
declare(strict_types=1);

namespace Softcode\Gls\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use Softcode\Gls\Api\ItemShippingRuleInterface;
use Softcode\Gls\Model\Config;
use Softcode\Gls\Model\Shippability;
use Softcode\Gls\Model\ShippingRule\ExcludedSkuRule;
use Softcode\Gls\Model\ShippingRule\ProductTypeRule;

class ShippabilityTest extends TestCase
{
    private function item(string $type, string $sku, ?object $parent = null): object
    {
        return new class ($type, $sku, $parent) {
            public function __construct(private string $type, private string $sku, private ?object $parent)
            {
            }

            public function getProductType(): string
            {
                return $this->type;
            }

            public function getSku(): string
            {
                return $this->sku;
            }

            public function getParentItem(): ?object
            {
                return $this->parent;
            }
        };
    }

    private function shippability(string $excludedSkus = '', array $extraRules = []): Shippability
    {
        $scope = $this->createMock(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnCallback(
            static fn (string $path) => $path === 'shipping/gls/excluded_skus' ? $excludedSkus : null
        );
        $config = new Config($scope);

        return new Shippability([new ProductTypeRule(), new ExcludedSkuRule($config), ...$extraRules]);
    }

    public function testPhysicalProductShips(): void
    {
        $this->assertTrue($this->shippability()->itemNeedsShipping($this->item('simple', 'MUG')));
    }

    public function testVirtualAndDownloadableNeverShip(): void
    {
        $s = $this->shippability();
        $this->assertFalse($s->itemNeedsShipping($this->item('virtual', 'GIFT')));
        $this->assertFalse($s->itemNeedsShipping($this->item('downloadable', 'EBOOK')));
    }

    public function testExcludedSkusFromConfig(): void
    {
        $s = $this->shippability("GAME-1, GAME-2\nGAME-3");
        $this->assertFalse($s->itemNeedsShipping($this->item('simple', 'GAME-3')));
        $this->assertTrue($s->itemNeedsShipping($this->item('simple', 'GAME-4')));
    }

    public function testChildItemsFollowTheirParent(): void
    {
        $parent = $this->item('configurable', 'SHIRT');
        $this->assertFalse($this->shippability()->itemNeedsShipping($this->item('simple', 'SHIRT-M', $parent)));
    }

    public function testProjectRulesCanExcludeItems(): void
    {
        $courses = new class implements ItemShippingRuleInterface {
            public function excludes($item): bool
            {
                return str_starts_with($item->getSku(), 'COURSE-');
            }
        };
        $s = $this->shippability('', [$courses]);

        $items = [$this->item('simple', 'COURSE-1'), $this->item('virtual', 'X')];
        $this->assertFalse($s->itemsNeedShipping($items));

        $items[] = $this->item('simple', 'BOOK');
        $this->assertTrue($s->itemsNeedShipping($items));
        $this->assertCount(1, $s->filterShippable($items));
    }
}
