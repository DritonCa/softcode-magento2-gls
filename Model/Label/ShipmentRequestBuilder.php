<?php
declare(strict_types=1);

namespace Softcode\Gls\Model\Label;

/**
 * Builds the XML body for the GLS Denmark CreateShipment web service.
 */
class ShipmentRequestBuilder
{
    /** ISO 3166-1 numeric codes, which the GLS DK API expects in <CountryNum>. */
    private const COUNTRY_NUM = [
        'DK' => 208, 'SE' => 752, 'NO' => 578, 'FI' => 246, 'DE' => 276, 'NL' => 528,
        'BE' => 56, 'PL' => 616, 'AT' => 40, 'FR' => 250, 'GB' => 826, 'IS' => 352,
        'FO' => 234, 'GL' => 304, 'EE' => 233, 'LV' => 428, 'LT' => 440,
    ];

    /**
     * @param array{username: string, password: string, customer_id: string, contact_id: ?string} $credentials
     */
    public function build(
        array $credentials,
        Recipient $recipient,
        float $weightKg,
        string $parcelShopId = '',
        string $reference = '',
        ?\DateTimeInterface $shipDate = null
    ): string {
        $date = ($shipDate ?? new \DateTimeImmutable('+1 day'))->format('Ymd');
        $countryNum = self::COUNTRY_NUM[strtoupper($recipient->countryIso2)] ?? self::COUNTRY_NUM['DK'];
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $weight = number_format($weightKg, 1, '.', '');

        return '<?xml version="1.0" encoding="utf-8"?>'
            . "<Shipment xmlns:xsd='http://www.w3.org/2001/XMLSchema' "
            . "xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'>"
            . '<UserName>' . $e($credentials['username']) . '</UserName>'
            . '<Password>' . $e($credentials['password']) . '</Password>'
            . '<Customerid>' . $e($credentials['customer_id']) . '</Customerid>'
            . '<Contactid>' . $e((string)($credentials['contact_id'] ?? '')) . '</Contactid>'
            . '<ShipmentDate>' . $date . '</ShipmentDate>'
            . '<Reference>' . $e($reference) . '</Reference>'
            . '<System/>'
            . '<Addresses><Delivery>'
            . '<Reference>' . $e($reference) . '</Reference>'
            . '<Name1>' . $e($recipient->name1) . '</Name1>'
            . '<Name2>' . $e($recipient->name2) . '</Name2>'
            . '<Street1>' . $e($recipient->street) . '</Street1>'
            . '<CountryNum>' . $countryNum . '</CountryNum>'
            . '<ZipCode>' . $e($recipient->postcode) . '</ZipCode>'
            . '<City>' . $e($recipient->city) . '</City>'
            . '<Contact>' . $e($recipient->contact) . '</Contact>'
            . '<Email>' . $e($recipient->email) . '</Email>'
            . '<Phone>' . $e($recipient->phone) . '</Phone>'
            . '<Mobile>' . $e($recipient->phone) . '</Mobile>'
            . '</Delivery></Addresses>'
            . '<Parcels><Parcel><Weight>' . $weight . '</Weight></Parcel></Parcels>'
            . '<Services>'
            . '<ShopDelivery>' . $e($parcelShopId) . '</ShopDelivery>'
            . '<NotificationEmail>' . $e($recipient->email) . '</NotificationEmail>'
            . '</Services>'
            . '</Shipment>';
    }
}
