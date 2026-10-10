<?php
declare(strict_types=1);

namespace Softcode\Gls\Model\Label;

/**
 * Normalises a phone number to international format for GLS SMS notifications.
 */
class PhoneFormatter
{
    private const CALLING_CODES = [
        'DK' => '45', 'SE' => '46', 'NO' => '47', 'FI' => '358', 'DE' => '49',
        'NL' => '31', 'BE' => '32', 'PL' => '48', 'AT' => '43', 'FR' => '33',
    ];

    public static function format(string $phone, string $countryIso2 = 'DK'): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }
        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($phone, '+')) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '00')) {
            return '+' . substr($digits, 2);
        }

        $code = self::CALLING_CODES[strtoupper($countryIso2)] ?? null;
        if ($code === null) {
            return $digits;
        }
        // Danish numbers are 8 digits; a 10-digit number starting with 45 already has the code.
        if ($code === '45' && strlen($digits) === 10 && str_starts_with($digits, '45')) {
            return '+' . $digits;
        }
        return '+' . $code . ltrim($digits, '0');
    }
}
