<?php

namespace App\Services\Qris;

use App\Services\Qris\DTOs\ValidationResult;

class QrisValidator
{
    /**
     * Validate a QRIS string for structural correctness and EMVCo/ASPI standard compliance.
     */
    public static function validate(string $qrisString): ValidationResult
    {
        $errors = [];
        $trimmed = trim($qrisString);

        if (empty($trimmed)) {
            return new ValidationResult(valid: false, errors: ['QRIS string is empty']);
        }

        // Must start with Payload Format Indicator "000201"
        if (!str_starts_with($trimmed, '000201')) {
            $errors[] = 'QRIS must start with Payload Format Indicator "000201"';
        }

        // Minimum length check (header + CRC = at least 20 chars)
        if (strlen($trimmed) < 20) {
            $errors[] = 'QRIS payload is too short (minimum 20 characters required)';
            return new ValidationResult(valid: false, errors: $errors);
        }

        // CRC16 Checksum validation
        $declaredCRC = strtoupper(substr($trimmed, -4));
        $dataWithoutCRC = substr($trimmed, 0, -4);
        $calculatedCRC = Crc16::calculate($dataWithoutCRC);

        if ($declaredCRC !== $calculatedCRC) {
            $errors[] = "CRC mismatch: expected {$calculatedCRC}, got {$declaredCRC}";
        }

        // Parse TLV elements
        $elements = TlvParser::parse($trimmed);
        if (empty($elements)) {
            $errors[] = 'Failed to parse TLV elements from payload';
            return new ValidationResult(valid: false, errors: $errors);
        }

        $tagMap = [];
        foreach ($elements as $el) {
            $tagMap[$el->tag] = $el;
        }

        // Required tags check
        $requiredTags = [
            '00' => 'Payload Format Indicator',
            '01' => 'Point of Initiation Method',
            '52' => 'Merchant Category Code',
            '53' => 'Transaction Currency',
            '58' => 'Country Code',
            '59' => 'Merchant Name',
            '60' => 'Merchant City',
            '63' => 'CRC',
        ];

        foreach ($requiredTags as $tag => $name) {
            if (!isset($tagMap[$tag])) {
                $errors[] = "Missing required tag {$tag} ({$name})";
            }
        }

        // Point of Initiation Method validation (must be 11 or 12)
        if (isset($tagMap['01'])) {
            $method = $tagMap['01']->value;
            if ($method !== '11' && $method !== '12') {
                $errors[] = "Invalid Point of Initiation Method: '{$method}' (must be '11' for static or '12' for dynamic)";
            }
        }

        // Check currency (Tag 53: 360 is IDR)
        if (isset($tagMap['53'])) {
            $currency = $tagMap['53']->value;
            if ($currency !== '360') {
                $errors[] = "Invalid or unsupported transaction currency: '{$currency}' (expected '360' for IDR)";
            }
        }

        // Country code (Tag 58: ID)
        if (isset($tagMap['58'])) {
            $country = strtoupper($tagMap['58']->value);
            if ($country !== 'ID') {
                $errors[] = "Invalid country code: '{$country}' (expected 'ID')";
            }
        }

        // At least one Merchant Account Information (tags 26-51)
        $hasMerchantInfo = false;
        foreach ($elements as $el) {
            if (ctype_digit($el->tag)) {
                $tagNum = (int) $el->tag;
                if ($tagNum >= 26 && $tagNum <= 51) {
                    $hasMerchantInfo = true;
                    break;
                }
            }
        }

        if (!$hasMerchantInfo) {
            $errors[] = 'No Merchant Account Information found (at least one tag between 26 and 51 is required)';
        }

        return new ValidationResult(valid: empty($errors), errors: $errors);
    }
}
