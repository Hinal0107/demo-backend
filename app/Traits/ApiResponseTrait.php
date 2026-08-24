<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    /**
     * Return a success response.
     */
    protected function successResponse($data = null, string $message = 'Request successful', int $code = 200, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error response.
     */
    protected function errorResponse(string $message = 'An error occurred', int $code = 400, array $errors = []): JsonResponse
    {
        if ($code < 100 || $code >= 600) {
            $code = 400;
        }
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Return a paginated success response.
     */
    protected function paginatedResponse($resourceCollection, string $message = 'Request successful'): JsonResponse
    {
        $paginated = $resourceCollection->resource->toArray();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resourceCollection,
            'meta' => [
                'current_page' => $paginated['current_page'] ?? null,
                'per_page' => $paginated['per_page'] ?? null,
                'total' => $paginated['total'] ?? null,
                'last_page' => $paginated['last_page'] ?? null,
            ]
        ]);
    }
}
