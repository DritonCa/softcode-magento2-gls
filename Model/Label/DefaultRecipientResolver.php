<?php
declare(strict_types=1);

namespace Softcode\Gls\Model\Label;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Softcode\Gls\Api\RecipientResolverInterface;

/**
 * Addresses the label to the order's shipping address (or billing address when the
 * order has none). A company name becomes the first line with "Att.: person" below,
 * otherwise the person's name stands alone.
 */
class DefaultRecipientResolver implements RecipientResolverInterface
{
    public function resolve(OrderInterface $order): Recipient
    {
        $address = $order->getShippingAddress() ?: $order->getBillingAddress();
        if (!$address) {
            throw new LocalizedException(__('The order has no address to ship to.'));
        }

        $person = trim($address->getFirstname() . ' ' . $address->getLastname());
        $company = trim((string)$address->getCompany());
        $email = (string)($address->getEmail() ?: $order->getCustomerEmail());
        $phone = PhoneFormatter::format((string)$address->getTelephone(), (string)$address->getCountryId());

        return new Recipient(
            $company !== '' ? $company : $person,
            $company !== '' && $person !== '' ? 'Att.: ' . $person : '',
            trim(implode(' ', (array)$address->getStreet())),
            (string)$address->getPostcode(),
            (string)$address->getCity(),
            strtoupper((string)$address->getCountryId() ?: 'DK'),
            $person,
            $email,
            $phone
        );
    }
}
