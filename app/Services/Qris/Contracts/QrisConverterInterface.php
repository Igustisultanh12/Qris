<?php

namespace App\Services\Qris\Contracts;

use App\Services\Qris\DTOs\FeeData;
use App\Services\Qris\DTOs\QrisConversionResult;
use App\Services\Qris\DTOs\QrisData;
use App\Services\Qris\DTOs\ValidationResult;

interface QrisConverterInterface
{
    public function parse(string $qris): QrisData;

    public function validate(string $qris): ValidationResult;

    public function convert(string $qris, int $amount, ?FeeData $fee = null): QrisConversionResult;
}
