<?php
declare(strict_types=1);

namespace Softcode\Gls\Api;

use Magento\Sales\Api\Data\OrderInterface;
use Softcode\Gls\Model\Label\Recipient;

/**
 * Decides who and where a GLS label is addressed to.
 *
 * The default implementation uses the order's shipping address (falling back to the
 * billing address). A checkout that stores the delivery address elsewhere replaces it
 * with a di.xml preference.
 */
interface RecipientResolverInterface
{
    public function resolve(OrderInterface $order): Recipient;
}
