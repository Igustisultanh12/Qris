<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'merchant_code' => $this->merchant_code,
            'name' => $this->name,
            'store_name' => $this->store_name,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'mcc' => $this->mcc,
            'acquirer_name' => $this->acquirer_name,
            'status' => $this->status,
            'fee_mode' => $this->fee_mode,
            'custom_fee_type' => $this->custom_fee_type,
            'custom_fee_value' => (float) $this->custom_fee_value,
            'has_qris' => $this->primaryQris !== null,
            'qris_metadata' => $this->primaryQris?->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
