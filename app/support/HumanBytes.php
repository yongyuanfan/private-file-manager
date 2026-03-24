<?php

namespace app\support;

final class HumanBytes
{
    public static function format(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $exp = (int) floor(log($bytes, 1024));
        $exp = min($exp, count($units) - 1);
        $value = $bytes / (1024 ** $exp);

        return (abs($value - round($value)) < 0.001 ? (string) (int) $value : number_format($value, 2, '.', ''))
            . ' ' . $units[$exp];
    }
}
