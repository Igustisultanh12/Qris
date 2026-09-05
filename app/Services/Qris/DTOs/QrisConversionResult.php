<?php

namespace App\Services\Qris\DTOs;

class QrisConversionResult
{
    /**
     * @param string[] $errors
     */
    public function __construct(
        public bool $success,
        public ?string $staticPayload = null,
        public ?string $dynamicPayload = null,
        public int $amount = 0,
        public int $fee = 0,
        public int $total = 0,
        public string $feeMode = 'charged_to_customer',
        public ?string $qrSvg = null,
        public ?string $qrBase64 = null,
        public ?string $crc = null,
        public array $errors = []
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'static_payload' => $this->staticPayload,
            'dynamic_payload' => $this->dynamicPayload,
            'amount' => $this->amount,
            'fee' => $this->fee,
            'total' => $this->total,
            'fee_mode' => $this->feeMode,
            'qr_svg' => $this->qrSvg,
            'qr_base64' => $this->qrBase64,
            'crc' => $this->crc,
            'errors' => $this->errors,
        ];
    }
}
