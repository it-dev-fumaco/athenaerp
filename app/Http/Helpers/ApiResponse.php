<?php

namespace App\Http\Helpers;

use Illuminate\Http\JsonResponse;

/**
 * Standardized API responses compatible with Frappe REST API format.
 *
 * @see https://docs.frappe.io/framework/user/en/api/rest
 */
class ApiResponse
{
    /**
     * Success response with data (Frappe format: {"data": ...}).
     */
    public static function data(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    /**
     * Success message (Frappe format: {"message": ...}).
     */
    public static function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }

    /**
     * Error response (Frappe format: {"exc": ..., "exc_type": ...}).
     */
    public static function error(string $message, string $excType = 'Exception', int $status = 422): JsonResponse
    {
        return response()->json([
            'exc' => $message,
            'exc_type' => $excType,
        ], $status);
    }

    /**
     * Legacy-compatible success (status, message, data).
     */
    public static function success(string $message = 'Success', mixed $data = null, int $status = 200): JsonResponse
    {
        $payload = ['status' => 1, 'message' => $message];
        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    /**
     * Legacy-compatible failure (status: 0, message).
     */
    public static function failure(string $message = 'Something went wrong.', int $status = 422): JsonResponse
    {
        return response()->json(['status' => 0, 'message' => $message], $status);
    }

    /**
     * Success with extra keys merged into payload (e.g. show_notif, item_code, data).
     */
    public static function successWith(string $message, array $extra = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge(['status' => 1, 'message' => $message], $extra), $status);
    }

    /**
     * Success using 'success' key (legacy compatibility).
     */
    public static function successLegacy(string $message, array $extra = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge(['success' => 1, 'message' => $message], $extra), $status);
    }

    /**
     * Failure using 'success' key (legacy compatibility).
     */
    public static function failureLegacy(string $message, int $status = 422, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['success' => 0, 'message' => $message], $extra), $status);
    }

    /**
     * Modal-style response (error, modal_title, modal_message).
     */
    public static function modal(bool $success, string $title, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'error' => $success ? 0 : 1,
            'modal_title' => $title,
            'modal_message' => $message,
        ], $status);
    }
}
