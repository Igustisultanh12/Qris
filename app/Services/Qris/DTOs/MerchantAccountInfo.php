<?php

namespace App\Services\Qris\DTOs;

class MerchantAccountInfo
{
    /**
     * @param TlvElement[] $fields
     */
    public function __construct(
        public string $tag,
        public string $globallyUniqueId,
        public ?string $merchantId = null,
        public ?string $merchantCriteria = null,
        public array $fields = []
    ) {}

    public function toArray(): array
    {
        return [
            'tag' => $this->tag,
            'globally_unique_id' => $this->globallyUniqueId,
            'merchant_id' => $this->merchantId,
            'merchant_criteria' => $this->merchantCriteria,
            'fields' => array_map(fn (TlvElement $f) => $f->toArray(), $this->fields),
        ];
    }
}
