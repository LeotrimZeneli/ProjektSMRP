<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\ApiException;

final class Validator
{
    public static function requiredString(array $input, string $key, int $maxLength): string
    {
        $value = $input[$key] ?? null;
        if (!is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > $maxLength) {
            throw new ApiException(422, $key . ' is required and must not exceed ' . $maxLength . ' characters.');
        }

        return trim($value);
    }

    public static function email(mixed $value): string
    {
        if (!is_string($value) || strlen(trim($value)) > 150 ||
            filter_var(trim($value), FILTER_VALIDATE_EMAIL) === false) {
            throw new ApiException(422, 'A valid email address is required.');
        }

        return strtolower(trim($value));
    }

    public static function positiveInt(mixed $value, string $field): int
    {
        if (!is_string($value) && !is_int($value)) {
            throw new ApiException(422, 'A valid ' . $field . ' is required.');
        }

        $integer = filter_var($value, FILTER_VALIDATE_INT);
        if ($integer === false || $integer < 1) {
            throw new ApiException(422, 'A valid ' . $field . ' is required.');
        }

        return $integer;
    }

    public static function oneOf(mixed $value, array $choices, string $message): string
    {
        if (!is_string($value) || !in_array($value, $choices, true)) {
            throw new ApiException(422, $message);
        }

        return $value;
    }
}