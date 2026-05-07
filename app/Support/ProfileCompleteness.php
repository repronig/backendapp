<?php

namespace App\Support;

class ProfileCompleteness
{
    public static function make(array $fields): array
    {
        $normalized = [];

        foreach ($fields as $key => $value) {
            $normalized[$key] = self::isFilled($value);
        }

        $total = count($normalized);
        $completed = count(array_filter($normalized));
        $percentage = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'completed_fields' => $completed,
            'total_fields' => $total,
            'percentage' => $percentage,
            'is_complete' => $completed === $total,
            'missing_fields' => array_keys(array_filter($normalized, fn (bool $filled) => ! $filled)),
        ];
    }

    protected static function isFilled(mixed $value): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return count(array_filter($value, fn (mixed $item) => self::isFilled($item))) > 0;
        }

        return true;
    }
}
