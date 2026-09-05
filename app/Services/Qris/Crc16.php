<?php

namespace App\Services\Qris;

class Crc16
{
    /**
     * Calculate CRC16-CCITT checksum for QRIS/EMVCo QR codes.
     * Polynomial: 0x1021, Init: 0xFFFF
     */
    public static function calculate(string $str): string
    {
        $crc = 0xFFFF;
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            $crc ^= (ord($str[$i]) << 8) & 0xFFFF;
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc & 0xFFFF), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Verify if the CRC at the end of a QRIS payload is valid.
     */
    public static function verify(string $qrisString): bool
    {
        $trimmed = trim($qrisString);
        if (strlen($trimmed) < 20) {
            return false;
        }

        $declaredCRC = strtoupper(substr($trimmed, -4));
        $dataWithoutCRC = substr($trimmed, 0, -4);
        $calculatedCRC = self::calculate($dataWithoutCRC);

        return $declaredCRC === $calculatedCRC;
    }
}
