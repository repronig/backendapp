<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

trait ApiResponse
{
    protected function success(
        string $message,
        mixed $data = null,
        int $status = 200,
        array $extra = []
    ): JsonResponse {
        $payload = array_merge([
            'message' => $message,
            'data' => $data,
        ], $extra);

        return response()->json($payload, $status);
    }

    protected function created(
        string $message,
        mixed $data = null,
        array $extra = []
    ): JsonResponse {
        return $this->success($message, $data, 201, $extra);
    }

    protected function paginated(
        string $message,
        LengthAwarePaginator $paginator,
        string $resourceClass
    ): JsonResponse {
        /** @var AnonymousResourceCollection $collection */
        $collection = $resourceClass::collection($paginator->getCollection());

        return response()->json([
            'message' => $message,
            'data' => $collection->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    protected function error(
        string $message,
        int $status = 400,
        ?array $errors = null,
        array $extra = []
    ): JsonResponse {
        $payload = array_merge([
            'message' => $message,
        ], $extra);

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
