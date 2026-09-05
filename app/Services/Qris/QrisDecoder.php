<?php

namespace App\Services\Qris;

class QrisDecoder
{
    /**
     * Decode and extract QRIS information from a payload or image.
     */
    public static function extractMetadata(string $qrisString): array
    {
        $validation = QrisValidator::validate($qrisString);
        if (!$validation->valid) {
            return [
                'valid' => false,
                'errors' => $validation->errors,
            ];
        }

        $data = QrisParser::parse($qrisString);
        return [
            'valid' => true,
            'errors' => [],
            'data' => $data->toArray(),
        ];
    }
}
