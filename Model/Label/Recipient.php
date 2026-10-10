<?php
declare(strict_types=1);

namespace Softcode\Gls\Model\Label;

/**
 * Who and where a GLS parcel is addressed to.
 */
class Recipient
{
    public function __construct(
        public readonly string $name1,
        public readonly string $name2,
        public readonly string $street,
        public readonly string $postcode,
        public readonly string $city,
        public readonly string $countryIso2,
        public readonly string $contact,
        public readonly string $email,
        public readonly string $phone
    ) {
    }
}
