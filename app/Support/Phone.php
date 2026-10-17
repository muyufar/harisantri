<?php

namespace App\Support;

class Phone
{
    public static function digits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    public static function normalize(string $phone): string
    {
        $digits = self::digits($phone);

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        if ($digits !== '' && ! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return $digits;
    }

    public static function display(string $phone): string
    {
        $digits = self::normalize($phone);

        if (str_starts_with($digits, '62')) {
            return '0'.substr($digits, 2);
        }

        return $digits;
    }

    public static function whatsappUrl(string $phone, string $text = ''): string
    {
        $url = 'https://wa.me/'.self::normalize($phone);

        if ($text !== '') {
            $url .= '?text='.rawurlencode($text);
        }

        return $url;
    }

    public static function matches(string $stored, string $input): bool
    {
        return hash_equals(hash('sha256', self::normalize($stored)), hash('sha256', self::normalize($input)));
    }
}
