<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

abstract class BaseApiController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;

    protected function applyDateRange(Builder $query, Request $request, string $column = 'created_at'): Builder
    {
        if ($request->filled('date_from')) {
            $query->whereDate($column, '>=', $request->date('date_from')->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate($column, '<=', $request->date('date_to')->toDateString());
        }

        return $query;
    }

    protected function applySorting(Builder $query, Request $request, array $allowedSorts, string $defaultSort = 'created_at', string $defaultDirection = 'desc'): Builder
    {
        $sort = $request->string('sort')->value();
        $direction = strtolower($request->string('direction', $defaultDirection)->value());
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : $defaultDirection;

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = $defaultSort;
        }

        return $query->orderBy($sort, $direction);
    }

    /** @var list<int> */
    protected const ALLOWED_PAGE_SIZES = [10, 20, 50, 100];

    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        $perPage = (int) $request->integer('per_page', $default);

        if ($perPage < 1) {
            return min(max($default, 1), $max);
        }

        $capped = min($perPage, $max);

        if (in_array($capped, self::ALLOWED_PAGE_SIZES, true)) {
            return $capped;
        }

        return min(max($default, 1), $max);
    }
}
