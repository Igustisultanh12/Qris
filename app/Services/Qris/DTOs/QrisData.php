<?php

namespace App\Services\Qris\DTOs;

class QrisData
{
    /**
     * @param MerchantAccountInfo[] $merchantAccountInfo
     * @param TlvElement[]|null $additionalData
     * @param TlvElement[] $raw
     */
    public function __construct(
        public string $version,
        public string $method, // 'static' | 'dynamic'
        public array $merchantAccountInfo,
        public string $merchantCategoryCode,
        public string $currency,
        public ?string $amount = null,
        public ?string $tipIndicator = null,
        public ?string $tipFixed = null,
        public ?string $tipPercentage = null,
        public string $countryCode = 'ID',
        public string $merchantName = '',
        public string $merchantCity = '',
        public string $postalCode = '',
        public ?array $additionalData = null,
        public string $crc = '',
        public array $raw = []
    ) {}

    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'method' => $this->method,
            'merchant_name' => $this->merchantName,
            'merchant_city' => $this->merchantCity,
            'merchant_category_code' => $this->merchantCategoryCode,
            'currency' => $this->currency,
            'amount' => $this->amount,
            'tip_indicator' => $this->tipIndicator,
            'tip_fixed' => $this->tipFixed,
            'tip_percentage' => $this->tipPercentage,
            'country_code' => $this->countryCode,
            'postal_code' => $this->postalCode,
            'merchant_account_info' => array_map(fn (MerchantAccountInfo $m) => $m->toArray(), $this->merchantAccountInfo),
            'additional_data' => $this->additionalData ? array_map(fn (TlvElement $t) => $t->toArray(), $this->additionalData) : null,
            'crc' => $this->crc,
            'raw' => array_map(fn (TlvElement $r) => $r->toArray(), $this->raw),
        ];
    }
}
