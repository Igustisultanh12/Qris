<?php

namespace App\Services\Qris;

use App\Services\Qris\Contracts\QrisConverterInterface;
use App\Services\Qris\DTOs\FeeData;
use App\Services\Qris\DTOs\QrisConversionResult;
use App\Services\Qris\DTOs\QrisData;
use App\Services\Qris\DTOs\ValidationResult;

class QrisConverter implements QrisConverterInterface
{
    public function parse(string $qris): QrisData
    {
        return QrisParser::parse($qris);
    }

    public function validate(string $qris): ValidationResult
    {
        return QrisValidator::validate($qris);
    }

    /**
     * Convert static QRIS to dynamic QRIS by injecting amount and optional fee.
     */
    public function convert(string $qris, int $amount, ?FeeData $fee = null): QrisConversionResult
    {
        $validation = $this->validate($qris);
        if (!$validation->valid) {
            return new QrisConversionResult(
                success: false,
                staticPayload: $qris,
                errors: $validation->errors
            );
        }

        if ($amount <= 0) {
            return new QrisConversionResult(
                success: false,
                staticPayload: $qris,
                errors: ['Transaction amount must be greater than zero']
            );
        }

        $parsed = $this->parse($qris);
        if ($parsed->method === 'dynamic') {
            return new QrisConversionResult(
                success: false,
                staticPayload: $qris,
                errors: ['Provided QRIS is already dynamic']
            );
        }

        $feeAmount = $fee ? $fee->calculateFee($amount) : 0;
        $total = ($fee && $fee->mode === 'charged_to_customer') ? ($amount + $feeAmount) : $amount;

        $elements = TlvParser::parse($qris);
        $result = [];
        $amountInserted = false;

        // Tags to skip and re-insert properly
        $managedTags = ['54', '55', '56', '57', '63'];

        foreach ($elements as $el) {
            if (in_array($el->tag, $managedTags, true)) {
                continue;
            }

            if ($el->tag === '01') {
                // Change Static (11) to Dynamic (12)
                $result[] = TlvParser::make('01', '12', 'Point of Initiation Method');
                continue;
            }

            // Insert Tag 54 (Amount) and Tag 55/56/57 (Fee) right before Tag 58 (Country Code)
            if ($el->tag === '58' && !$amountInserted) {
                // Amount in IDR is integer formatted
                $result[] = TlvParser::make('54', (string) $total, 'Transaction Amount');

                if ($fee && $fee->type !== 'none' && $fee->value > 0) {
                    if ($fee->type === 'fixed') {
                        $result[] = TlvParser::make('55', '02', 'Tip or Convenience Indicator');
                        $result[] = TlvParser::make('56', (string) ((int) round($fee->value)), 'Value of Convenience Fee (Fixed)');
                    } elseif ($fee->type === 'percentage') {
                        $result[] = TlvParser::make('55', '03', 'Tip or Convenience Indicator');
                        $formattedPercent = rtrim(rtrim(number_format($fee->value, 2, '.', ''), '0'), '.');
                        $result[] = TlvParser::make('57', $formattedPercent, 'Value of Convenience Fee (%)');
                    }
                }

                $amountInserted = true;
            }

            $result[] = $el;
        }

        // If tag 58 was not found for some reason, append amount
        if (!$amountInserted) {
            $result[] = TlvParser::make('54', (string) $total, 'Transaction Amount');
            if ($fee && $fee->type !== 'none' && $fee->value > 0) {
                if ($fee->type === 'fixed') {
                    $result[] = TlvParser::make('55', '02', 'Tip or Convenience Indicator');
                    $result[] = TlvParser::make('56', (string) ((int) round($fee->value)), 'Value of Convenience Fee (Fixed)');
                } elseif ($fee->type === 'percentage') {
                    $result[] = TlvParser::make('55', '03', 'Tip or Convenience Indicator');
                    $formattedPercent = rtrim(rtrim(number_format($fee->value, 2, '.', ''), '0'), '.');
                    $result[] = TlvParser::make('57', $formattedPercent, 'Value of Convenience Fee (%)');
                }
            }
        }

        // Rebuild string without CRC
        $withoutCrc = TlvParser::build($result);
        $crcInput = $withoutCrc . '6304';
        $crc = Crc16::calculate($crcInput);
        $dynamicPayload = $crcInput . $crc;

        return new QrisConversionResult(
            success: true,
            staticPayload: $qris,
            dynamicPayload: $dynamicPayload,
            amount: $amount,
            fee: $feeAmount,
            total: $total,
            feeMode: $fee?->mode ?? 'charged_to_customer',
            crc: $crc,
            errors: []
        );
    }
}
