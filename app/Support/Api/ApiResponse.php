<?php

namespace App\Support\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Thành công',
        int $status = 200,
        array $meta = [],
        array $extra = []
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        if ($extra !== []) {
            $payload = array_merge($payload, $extra);
        }

        return response()->json($payload, $status);
    }

    public static function error(
        string $message = 'Có lỗi xảy ra',
        int $status = 400,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    public static function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'Thành công',
        int $status = 200
    ): JsonResponse {
        return self::success(
            data: $paginator->items(),
            message: $message,
            status: $status,
            meta: [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
        );
    }
}
