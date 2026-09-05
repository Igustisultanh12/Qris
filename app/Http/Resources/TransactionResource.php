<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $qrImageUrl = $this->qr_image_path
            ? url(Storage::url($this->qr_image_path))
            : null;

        return [
            'transaction_id' => $this->transaction_number,
            'uuid' => $this->uuid,
            'reference' => $this->reference,
            'merchant_id' => $this->merchant?->uuid ?? $this->merchant?->merchant_code,
            'merchant_name' => $this->merchant?->name,
            'amount' => $this->amount,
            'fee' => $this->fee,
            'total' => $this->total,
            'fee_mode' => $this->fee_mode,
            'qris_string' => $this->qris_dynamic,
            'qr_image_url' => $qrImageUrl,
            'status' => $this->status,
            'source' => $this->source,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
