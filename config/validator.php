<?php
declare(strict_types=1);
class Validator {
    public const REGIONS = ['Himalayan', 'Hilly', 'Terai'];
    public const SEASONS = ['Spring', 'Summer', 'Autumn', 'Winter'];
    public const CATEGORIES = ['adventure', 'cultural', 'nature', 'food', 'wellness', 'photography'];
    public static function text(mixed $value, string $name, int $min = 1, int $max = 200): string {
        if (!is_string($value)) throw new InvalidArgumentException("{$name} must be text.");
        $value = trim($value);
        if (mb_strlen($value) < $min || mb_strlen($value) > $max) throw new InvalidArgumentException("{$name} must contain {$min}–{$max} characters.");
        return $value;
    }
    public static function number(mixed $value, string $name, float $min, float $max): float {
        if (!is_numeric($value) || !is_finite((float)$value) || (float)$value < $min || (float)$value > $max) throw new InvalidArgumentException("{$name} must be between {$min} and {$max}.");
        return (float)$value;
    }
    public static function choice(mixed $value, array $choices, string $name): string {
        if (!is_string($value) || !in_array($value, $choices, true)) throw new InvalidArgumentException("Choose a valid {$name}.");
        return $value;
    }
    public static function seasons(mixed $value): string {
        if (is_string($value)) $value = array_map('trim', explode(',', $value));
        if (!is_array($value) || !$value) throw new InvalidArgumentException('Choose at least one season.');
        return implode(',', array_unique(array_map(fn($s) => self::choice($s, self::SEASONS, 'season'), $value)));
    }
    public static function sanitizeString(?string $value): string { return trim($value ?? ''); }
    public static function isValidEmail(string $value): bool { return filter_var($value, FILTER_VALIDATE_EMAIL) !== false; }
    public static function isMinLength(string $value, int $min): bool { return mb_strlen($value) >= $min; }
    public static function isPositiveNumber(mixed $value): bool { return is_numeric($value) && (float)$value > 0; }
    public static function isValidDate(string $value, string $format = 'Y-m-d'): bool { $date = DateTimeImmutable::createFromFormat('!' . $format, $value); return $date && $date->format($format) === $value; }
}
