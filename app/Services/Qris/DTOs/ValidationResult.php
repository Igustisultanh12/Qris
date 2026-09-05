<?php

namespace App\Services\Qris\DTOs;

class ValidationResult
{
    /**
     * @param string[] $errors
     */
    public function __construct(
        public bool $valid,
        public array $errors = []
    ) {}

    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'errors' => $this->errors,
        ];
    }
}
