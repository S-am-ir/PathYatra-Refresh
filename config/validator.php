<?php
/**
 * YatraPath - Validation & Sanitization Helpers
 */
declare(strict_types=1);

class Validator {
    public static function sanitizeString(?string $val): string {
        return htmlspecialchars(trim($val ?? ''), ENT_QUOTES, 'UTF-8');
    }

    public static function isValidEmail(string $email): bool {
        return (bool)filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }

    public static function isMinLength(string $val, int $min): bool {
        return mb_strlen(trim($val)) >= $min;
    }

    public static function isPositiveNumber(mixed $val): bool {
        return is_numeric($val) && (float)$val > 0;
    }

    public static function isValidDate(string $dateStr, string $format = 'Y-m-d'): bool {
        $d = DateTime::createFromFormat($format, trim($dateStr));
        return $d && $d->format($format) === trim($dateStr);
    }

    public static function sanitizeArray(array $arr): array {
        $clean = [];
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $clean[$key] = self::sanitizeArray($val);
            } elseif (is_string($val)) {
                $clean[$key] = self::sanitizeString($val);
            } else {
                $clean[$key] = $val;
            }
        }
        return $clean;
    }
}
