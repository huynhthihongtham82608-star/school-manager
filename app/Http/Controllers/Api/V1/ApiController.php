<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

abstract class ApiController extends Controller
{
    protected function success(
        mixed $data = null,
        string $message = 'Thành công',
        int $status = 200,
        array $meta = [],
        array $extra = []
    ): JsonResponse {
        return ApiResponse::success($data, $message, $status, $meta, $extra);
    }

    protected function error(
        string $message = 'Có lỗi xảy ra',
        int $status = 400,
        array $errors = [],
        array $meta = []
    ): JsonResponse {
        return ApiResponse::error($message, $status, $errors, $meta);
    }

    protected function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'Thành công',
        int $status = 200
    ): JsonResponse {
        return ApiResponse::paginated($paginator, $message, $status);
    }
}
