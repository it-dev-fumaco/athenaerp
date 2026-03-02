<?php

namespace App\Services\ERP;

use App\Exceptions\ErpAuthenticationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Dedicated HTTP client for Frappe/ERPNext REST API.
 *
 * - Handles all HTTP communication with ERP
 * - Attaches Authorization header (caller provides headers; never log credentials)
 * - Throws ErpAuthenticationException on 401
 * - Logs URL, method, status; body only on failure to avoid log bloat
 */
class ErpClient
{
    private const HTTP_UNAUTHORIZED = 401;

    /**
     * Execute an HTTP request to the ERP API.
     *
     * @param  string  $method  GET, POST, PUT, DELETE
     * @param  string  $url  Full URL (e.g. https://erp.example.com/api/resource/Stock%20Entry/STE-001)
     * @param  array  $options  'headers' => array (required), 'query' => array (GET), 'body' => array (POST/PUT)
     * @return array Decoded JSON response
     *
     * @throws \App\Exceptions\ErpAuthenticationException When ERP returns 401
     */
    public function request(string $method, string $url, array $options = []): array
    {
        $headers = $options['headers'] ?? [];
        $query = $options['query'] ?? [];
        $body = $options['body'] ?? [];

        $http = Http::withHeaders($headers);

        $response = match (strtoupper($method)) {
            'GET' => $http->get($url, $query),
            'POST' => $http->post($url, $body),
            'PUT' => $http->put($url, $body),
            'DELETE' => $http->delete($url),
            default => throw new \InvalidArgumentException("Invalid HTTP method: {$method}"),
        };

        $status = $response->status();
        $responseBody = $response->body();

        $this->logRequest($method, $url, $status, $responseBody);

        if ($status === self::HTTP_UNAUTHORIZED) {
            throw new ErpAuthenticationException('ERP returned 401 Unauthorized.');
        }

        return $response->json() ?? [];
    }

    /**
     * Log request/response. Never log API key or secret.
     * Body is logged only on failure to avoid large success payloads in logs.
     */
    private function logRequest(string $method, string $url, int $status, string $body): void
    {
        $context = [
            'url' => $url,
            'method' => $method,
            'status' => $status,
        ];
        if ($status >= 400) {
            $context['body'] = $body;
            Log::warning('ERP request failed', $context);
        } else {
            Log::debug('ERP request', $context);
        }
    }
}
