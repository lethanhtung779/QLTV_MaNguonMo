<?php
declare(strict_types=1);

namespace Library\Services;

final class IsbnValidator
{
    public static function normalize(?string $value): string
    {
        return strtoupper((string) preg_replace('/[^0-9Xx]/', '', (string) $value));
    }

    public static function isValid(?string $value): bool
    {
        $isbn = self::normalize($value);
        if (strlen($isbn) === 10) {
            $sum = 0;
            for ($index = 0; $index < 10; $index++) {
                $character = $isbn[$index];
                if ($character === 'X') {
                    if ($index !== 9) return false;
                    $digit = 10;
                } elseif (ctype_digit($character)) {
                    $digit = (int) $character;
                } else {
                    return false;
                }
                $sum += (10 - $index) * $digit;
            }
            return $sum % 11 === 0;
        }

        if (strlen($isbn) === 13 && ctype_digit($isbn)) {
            $sum = 0;
            for ($index = 0; $index < 12; $index++) {
                $sum += (int) $isbn[$index] * ($index % 2 === 0 ? 1 : 3);
            }
            return (10 - ($sum % 10)) % 10 === (int) $isbn[12];
        }

        return false;
    }

    public static function type(?string $value): ?string
    {
        $length = strlen(self::normalize($value));
        return $length === 10 ? 'ISBN-10' : ($length === 13 ? 'ISBN-13' : null);
    }
}
