<?php

namespace App\Services\Qris;

use App\Services\Qris\DTOs\TlvElement;

class TlvParser
{
    /**
     * Map of known EMVCo / QRIS tag IDs to human-readable names.
     */
    public const TAG_NAMES = [
        '00' => 'Payload Format Indicator',
        '01' => 'Point of Initiation Method',
        '02' => 'Visa',
        '03' => 'Mastercard',
        '04' => 'Mastercard',
        '15' => 'Visa',
        '26' => 'Merchant Account Information',
        '27' => 'Merchant Account Information',
        '28' => 'Merchant Account Information',
        '29' => 'Merchant Account Information',
        '30' => 'Merchant Account Information',
        '31' => 'Merchant Account Information',
        '32' => 'Merchant Account Information',
        '33' => 'Merchant Account Information',
        '34' => 'Merchant Account Information',
        '35' => 'Merchant Account Information',
        '36' => 'Merchant Account Information',
        '37' => 'Merchant Account Information',
        '38' => 'Merchant Account Information',
        '39' => 'Merchant Account Information',
        '40' => 'Merchant Account Information',
        '41' => 'Merchant Account Information',
        '42' => 'Merchant Account Information',
        '43' => 'Merchant Account Information',
        '44' => 'Merchant Account Information',
        '45' => 'Merchant Account Information',
        '46' => 'Merchant Account Information',
        '47' => 'Merchant Account Information',
        '48' => 'Merchant Account Information',
        '49' => 'Merchant Account Information',
        '50' => 'Merchant Account Information',
        '51' => 'Merchant Account Information',
        '52' => 'Merchant Category Code',
        '53' => 'Transaction Currency',
        '54' => 'Transaction Amount',
        '55' => 'Tip or Convenience Indicator',
        '56' => 'Value of Convenience Fee (Fixed)',
        '57' => 'Value of Convenience Fee (%)',
        '58' => 'Country Code',
        '59' => 'Merchant Name',
        '60' => 'Merchant City',
        '61' => 'Postal Code',
        '62' => 'Additional Data Field',
        '63' => 'CRC',
    ];

    /**
     * Parse raw string into an array of TlvElement objects.
     *
     * @return TlvElement[]
     */
    public static function parse(string $data): array
    {
        $elements = [];
        $pos = 0;
        $len = strlen($data);

        while ($pos < $len) {
            if ($pos + 4 > $len) {
                break;
            }

            $tag = substr($data, $pos, 2);
            $lengthStr = substr($data, $pos + 2, 2);

            if (!ctype_digit($lengthStr)) {
                break;
            }

            $length = (int) $lengthStr;
            if ($pos + 4 + $length > $len) {
                break;
            }

            $value = substr($data, $pos + 4, $length);
            $name = self::TAG_NAMES[$tag] ?? "Unknown ({$tag})";

            $children = null;
            if (self::isNestedTag($tag)) {
                $children = self::parse($value);
            }

            $elements[] = new TlvElement(
                tag: $tag,
                name: $name,
                length: $length,
                value: $value,
                children: $children
            );

            $pos += 4 + $length;
        }

        return $elements;
    }

    /**
     * Rebuild a string from an array of TlvElement objects.
     *
     * @param TlvElement[] $elements
     */
    public static function build(array $elements): string
    {
        $output = '';

        foreach ($elements as $el) {
            $value = $el->children !== null && count($el->children) > 0
                ? self::build($el->children)
                : $el->value;

            $length = str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT);
            $output .= $el->tag . $length . $value;
        }

        return $output;
    }

    /**
     * Helper to construct a new TlvElement.
     */
    public static function make(string $tag, string $value, ?string $name = null, ?array $children = null): TlvElement
    {
        return new TlvElement(
            tag: $tag,
            name: $name ?? (self::TAG_NAMES[$tag] ?? "Tag {$tag}"),
            length: strlen($value),
            value: $value,
            children: $children
        );
    }

    /**
     * Determine if tag contains nested TLVs.
     */
    public static function isNestedTag(string $tag): bool
    {
        if ($tag === '62') {
            return true;
        }

        if (ctype_digit($tag)) {
            $tagNum = (int) $tag;
            return $tagNum >= 26 && $tagNum <= 51;
        }

        return false;
    }
}
