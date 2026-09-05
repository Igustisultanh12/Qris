<?php

namespace App\Services\Qris;

use App\Services\Qris\DTOs\MerchantAccountInfo;
use App\Services\Qris\DTOs\QrisData;
use App\Services\Qris\DTOs\TlvElement;

class QrisParser
{
    /**
     * Parse a raw QRIS string into a structured QrisData object.
     */
    public static function parse(string $qrisString): QrisData
    {
        $raw = TlvParser::parse(trim($qrisString));

        $findTag = function (string $tag) use ($raw): ?TlvElement {
            foreach ($raw as $el) {
                if ($el->tag === $tag) {
                    return $el;
                }
            }
            return null;
        };

        // Method: static or dynamic
        $methodValue = $findTag('01')?->value;
        $method = ($methodValue === '12') ? 'dynamic' : 'static';

        // Tip indicator
        $tipValue = $findTag('55')?->value;
        $tipIndicator = match ($tipValue) {
            '01' => 'prompt',
            '02' => 'fixed',
            '03' => 'percentage',
            default => null,
        };

        // Merchant account info (tags 26..51)
        $merchantAccountInfo = [];
        foreach ($raw as $el) {
            if (ctype_digit($el->tag)) {
                $tagNum = (int) $el->tag;
                if ($tagNum >= 26 && $tagNum <= 51 && $el->children !== null) {
                    $children = $el->children;

                    $findChild = function (string $cTag) use ($children): ?string {
                        foreach ($children as $c) {
                            if ($c->tag === $cTag) {
                                return $c->value;
                            }
                        }
                        return null;
                    };

                    $merchantAccountInfo[] = new MerchantAccountInfo(
                        tag: $el->tag,
                        globallyUniqueId: $findChild('00') ?? '',
                        merchantId: $findChild('01') ?? $findChild('02'),
                        merchantCriteria: $findChild('03'),
                        fields: $children
                    );
                }
            }
        }

        return new QrisData(
            version: $findTag('00')?->value ?? '01',
            method: $method,
            merchantAccountInfo: $merchantAccountInfo,
            merchantCategoryCode: $findTag('52')?->value ?? '',
            currency: $findTag('53')?->value ?? '360',
            amount: $findTag('54')?->value,
            tipIndicator: $tipIndicator,
            tipFixed: $findTag('56')?->value,
            tipPercentage: $findTag('57')?->value,
            countryCode: $findTag('58')?->value ?? 'ID',
            merchantName: $findTag('59')?->value ?? '',
            merchantCity: $findTag('60')?->value ?? '',
            postalCode: $findTag('61')?->value ?? '',
            additionalData: $findTag('62')?->children,
            crc: $findTag('63')?->value ?? '',
            raw: $raw
        );
    }
}
