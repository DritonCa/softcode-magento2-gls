<?php
declare(strict_types=1);

namespace Softcode\Gls\Test\Unit\Model\Label;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Softcode\Gls\Model\Label\LabelClient;
use Softcode\Gls\Model\Label\ParcelSplitter;
use Softcode\Gls\Model\Label\PhoneFormatter;
use Softcode\Gls\Model\Label\Recipient;
use Softcode\Gls\Model\Label\ShipmentRequestBuilder;

/**
 * The pure parts of label creation: parcel splitting, phone numbers, the request
 * XML and parsing the GLS response.
 */
class LabelBuildingTest extends TestCase
{
    public function testSplitsHeavyOrdersIntoEqualParcels(): void
    {
        $splitter = new ParcelSplitter();

        $this->assertSame([10.0, 10.0], $splitter->split(20.0, 15.0));
        $this->assertSame([12.0], $splitter->split(12.0, 15.0));
        $this->assertSame([31.0], $splitter->split(31.0, 0.0), '0 disables splitting');
        $this->assertSame([0.1], $splitter->split(0.0, 15.0), 'GLS needs a positive weight');
    }

    public function testParcelCountIsCapped(): void
    {
        $this->assertCount(ParcelSplitter::MAX_PARCELS, (new ParcelSplitter())->split(5000.0, 1.0));
    }

    /**
     * @dataProvider phoneProvider
     */
    public function testFormatsPhoneNumbers(string $input, string $country, string $expected): void
    {
        $this->assertSame($expected, PhoneFormatter::format($input, $country));
    }

    public static function phoneProvider(): array
    {
        return [
            'danish 8 digits' => ['12 34 56 78', 'DK', '+4512345678'],
            'already 45 prefixed' => ['4512345678', 'DK', '+4512345678'],
            'plus kept' => ['+46 70 123 45 67', 'DK', '+46701234567'],
            '00 prefix' => ['0046701234567', 'DK', '+46701234567'],
            'swedish local' => ['070-123 45 67', 'SE', '+46701234567'],
            'empty' => ['', 'DK', ''],
        ];
    }

    public function testRequestXmlEscapesValuesAndSetsShopDelivery(): void
    {
        $xml = (new ShipmentRequestBuilder())->build(
            ['username' => 'u', 'password' => 'p<&>', 'customer_id' => '42', 'contact_id' => null],
            new Recipient('Firma & Co', 'Att.: Jens', 'Vej 1', '2400', 'København NV', 'DK', 'Jens', 'j@x.dk', '+4512345678'),
            2.25,
            '96123',
            '100000123',
            new \DateTimeImmutable('2026-10-10')
        );

        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc, 'request must be valid XML');
        $this->assertSame('p<&>', (string)$doc->Password);
        $this->assertSame('Firma & Co', (string)$doc->Addresses->Delivery->Name1);
        $this->assertSame('208', (string)$doc->Addresses->Delivery->CountryNum);
        $this->assertSame('2.3', (string)$doc->Parcels->Parcel->Weight);
        $this->assertSame('96123', (string)$doc->Services->ShopDelivery);
        $this->assertSame('20261010', (string)$doc->ShipmentDate);
        $this->assertSame('100000123', (string)$doc->Reference);
    }

    public function testRequestUsesNumericCountryCode(): void
    {
        $xml = (new ShipmentRequestBuilder())->build(
            ['username' => 'u', 'password' => 'p', 'customer_id' => '1', 'contact_id' => '2'],
            new Recipient('A', '', 'Gatan 1', '11122', 'Stockholm', 'SE', 'A', 'a@x.se', ''),
            1.0
        );
        $this->assertSame('752', (string)simplexml_load_string($xml)->Addresses->Delivery->CountryNum);
    }

    public function testParsesGlsResponse(): void
    {
        $body = '<CreateShipmentResult><PDF>' . base64_encode('%PDF-1.4 label') . '</PDF>'
            . '<ConsignmentId>00012345</ConsignmentId></CreateShipmentResult>';

        $this->assertSame(
            ['pdf' => '%PDF-1.4 label', 'consignment_id' => '00012345'],
            LabelClient::parseResponse($body)
        );
    }

    public function testRejectsResponseWithoutLabel(): void
    {
        $this->expectException(LocalizedException::class);
        LabelClient::parseResponse('<Error>Invalid credentials</Error>');
    }
}
