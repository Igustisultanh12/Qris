<?php

namespace App\Services\Qris\DTOs;

class FeeData
{
    public function __construct(
        public string $type, // 'none', 'fixed', 'percentage'
        public float $value = 0,
        public string $mode = 'charged_to_customer' // 'absorbed', 'charged_to_customer'
    ) {}

    public function calculateFee(int $amount): int
    {
        if ($this->type === 'fixed') {
            return (int) round($this->value);
        }

        if ($this->type === 'percentage') {
            return (int) round(($amount * $this->value) / 100);
        }

        return 0;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->value,
            'mode' => $this->mode,
        ];
    }
}
