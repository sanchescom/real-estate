<?php

declare(strict_types=1);

namespace App\Shared\App;

use Illuminate\Http\JsonResponse;

final readonly class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, string|null>  $links
     */
    public function data(mixed $data, array $meta = [], array $links = []): JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
            'meta' => $meta,
            'links' => $links,
        ]);
    }

    public function error(string $title, int $status, ?string $detail = null): JsonResponse
    {
        $error = ['status' => (string) $status, 'title' => $title];

        if ($detail !== null) {
            $error['detail'] = $detail;
        }

        return new JsonResponse(['errors' => [$error]], $status);
    }

    /** @param  array<string, list<string>>  $errors */
    public function validationErrors(array $errors): JsonResponse
    {
        $formatted = [];

        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $formatted[] = [
                    'status' => 422,
                    'title' => 'Validation Error',
                    'detail' => $message,
                    'source' => ['pointer' => '/'.str_replace('.', '/', $field)],
                ];
            }
        }

        return new JsonResponse(['errors' => $formatted], 422);
    }
}
