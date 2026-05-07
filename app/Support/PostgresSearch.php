<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Case-insensitive search helpers using PostgreSQL `ILIKE`.
 *
 * {@see whereColumnIlike()} and {@see whereAnyColumnIlike()} are PostgreSQL-only.
 * {@see whereColumnIlikeCompatible()} falls back to `LOWER(column) LIKE` on non-PostgreSQL drivers (e.g. SQLite tests).
 * Use {@see wrapPattern()} so user input is escaped for LIKE wildcards.
 */
final class PostgresSearch
{
    public static function escapeIlikePattern(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }

    public static function wrapPattern(string $needle): string
    {
        return '%'.self::escapeIlikePattern($needle).'%';
    }

    public static function whereColumnIlike(Builder $query, string $column, string $needle, string $boolean = 'and'): Builder
    {
        return $query->where($column, 'ilike', self::wrapPattern($needle), $boolean);
    }

    /**
     * Case-insensitive substring match: PostgreSQL uses ILIKE; SQLite / MySQL use LOWER … LIKE for tests and portability.
     */
    public static function whereColumnIlikeCompatible(Builder $query, string $column, string $needle, string $boolean = 'and'): Builder
    {
        $pattern = self::wrapPattern($needle);

        if ($query->getConnection()->getDriverName() === 'pgsql') {
            return $query->where($column, 'ilike', $pattern, $boolean);
        }

        $wrapped = $query->getGrammar()->wrap($column);

        return $query->whereRaw('LOWER('.$wrapped.') LIKE LOWER(?)', [$pattern], $boolean);
    }

    /**
     * @param  list<string>  $columns
     */
    public static function whereAnyColumnIlike(Builder $query, array $columns, string $needle): Builder
    {
        if ($columns === []) {
            return $query;
        }

        $pattern = self::wrapPattern($needle);

        return $query->where(function (Builder $inner) use ($columns, $pattern) {
            foreach ($columns as $index => $column) {
                if ($index === 0) {
                    $inner->where($column, 'ilike', $pattern);
                } else {
                    $inner->orWhere($column, 'ilike', $pattern);
                }
            }
        });
    }
}
