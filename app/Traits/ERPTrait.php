<?php

namespace App\Traits;

use App\Exceptions\ErpAuthenticationException;
use App\Services\ERP\ErpClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Frappe REST API integration.
 *
 * @see https://docs.frappe.io/framework/user/en/api/rest
 *
 * Authentication: Token-based (api_key:api_secret in Authorization header)
 * Endpoints: GET/POST /api/resource/:doctype, GET/PUT/DELETE /api/resource/:doctype/:name
 */
trait ERPTrait
{
    /**
     * ERP API base URL (no trailing slash).
     */
    private function getErpBaseUrl(): string
    {
        return rtrim(config('erp.api_base_url') ?? config('services.erp.api_base_url'), '/');
    }

    /**
     * Get headers for Frappe REST API requests.
     * Uses token-based auth: "token api_key:api_secret" per Frappe docs.
     * Credentials from config/erp.php when system-generated, else current user.
     */
    public function getErpHeaders(bool $systemGenerated = true): array
    {
        if ($systemGenerated) {
            $apiKey = config('erp.api_key') ?? config('services.erp.api_key');
            $apiSecret = config('erp.api_secret_key') ?? config('services.erp.api_secret_key');
        } else {
            $apiKey = Auth::user()->api_key;
            $apiSecret = Auth::user()->api_secret;
        }

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'token '.$apiKey.':'.$apiSecret,
            'Accept-Language' => 'en',
        ];
    }

    /**
     * Execute Frappe REST API operation via ErpClient.
     * On 401, ErpClient throws ErpAuthenticationException and execution stops.
     *
     * @param  string  $method  GET, POST, PUT, DELETE
     * @param  string  $doctype  Frappe DocType (e.g. "Stock Entry", "Bin")
     * @param  string|null  $name  Document name for read/update/delete
     * @param  array  $payload  For GET: query params. For POST/PUT: request body
     * @param  bool  $systemGenerated  Use system API key vs logged-in user's key
     * @return array Frappe response with 'data' key; 'exception' or 'exc' on error
     */
    private function erpOperation($method, $doctype, $name = null, $payload = [], $systemGenerated = false)
    {
        $url = $this->getErpBaseUrl().'/api/resource/'.$doctype;
        if ($name !== null && $name !== '') {
            $url .= '/'.ltrim($name, '/');
        }

        $options = [
            'headers' => $this->getErpHeaders($systemGenerated),
        ];

        if (strtoupper($method) === 'GET') {
            $options['query'] = $payload;
        } else {
            $options['body'] = $payload;
        }

        try {
            $client = app(ErpClient::class);
            $result = $client->request($method, $url, $options);
            $this->logErpErrorIfPresent($result, $url, $method);

            return $result;
        } catch (ErpAuthenticationException $e) {
            throw $e;
        } catch (\Throwable $th) {
            Log::error('ERP request error', [
                'method' => $method,
                'doctype' => $doctype,
                'name' => $name,
                'message' => $th->getMessage(),
            ]);

            $message = $this->isErpConnectionError($th->getMessage())
                ? self::erpConnectionUnavailableMessage()
                : $th->getMessage();

            return ['error' => 1, 'message' => $message];
        }
    }

    /**
     * User-facing message when ERP/API is unreachable.
     */
    public static function erpConnectionUnavailableMessage(): string
    {
        return 'ERP Connection unavailable via API';
    }

    /**
     * Whether the error indicates ERP/API connection failure (timeout, connection refused, etc.).
     */
    public function isErpConnectionError(string $message): bool
    {
        $lower = strtolower($message);
        return str_contains($lower, 'timed out')
            || str_contains($lower, 'cURL error 28')
            || str_contains($lower, 'connection refused')
            || str_contains($lower, 'could not resolve host')
            || str_contains($lower, 'cURL error 6')
            || str_contains($lower, 'cURL error 7')
            || str_contains($lower, 'connection')
            && (str_contains($lower, 'unavailable') || str_contains($lower, 'failed'));
    }

    /**
     * List documents from Frappe REST API.
     *
     * @see https://docs.frappe.io/framework/user/en/api/rest#listing-documents
     *
     * @param  string  $doctype  Frappe DocType
     * @param  array  $params  Query params: fields, filters, or_filters, order_by, limit_start, limit_page_length
     * @param  bool  $systemGenerated  Use system API key
     * @return array Response with 'data' array of records
     */
    public function erpList(string $doctype, array $params = [], bool $systemGenerated = false): array
    {
        $allowedParams = ['fields', 'filters', 'or_filters', 'order_by', 'limit_start', 'limit_page_length', 'limit', 'expand', 'as_dict'];
        $queryParams = array_intersect_key($params, array_flip($allowedParams));

        foreach (['fields', 'filters', 'or_filters', 'expand'] as $jsonParam) {
            if (isset($queryParams[$jsonParam]) && is_array($queryParams[$jsonParam])) {
                $queryParams[$jsonParam] = json_encode($queryParams[$jsonParam]);
            }
        }

        return $this->erpOperation('get', $doctype, null, $queryParams, $systemGenerated);
    }

    /**
     * Read a single document from Frappe REST API.
     *
     * @see https://docs.frappe.io/framework/user/en/api/rest#read
     */
    public function erpGet(string $doctype, string $name, array $queryParams = [], bool $systemGenerated = false): array
    {
        return $this->erpOperation('get', $doctype, $name, $queryParams, $systemGenerated);
    }

    /**
     * Create a document via Frappe REST API.
     *
     * @see https://docs.frappe.io/framework/user/en/api/rest#create
     */
    public function erpPost(string $doctype, array $data, bool $systemGenerated = false): array
    {
        return $this->erpOperation('post', $doctype, null, $data, $systemGenerated);
    }

    /**
     * Update a document via Frappe REST API.
     *
     * @see https://docs.frappe.io/framework/user/en/api/rest#update
     */
    public function erpPut(string $doctype, string $name, array $data, bool $systemGenerated = false): array
    {
        return $this->erpOperation('put', $doctype, $name, $data, $systemGenerated);
    }

    /**
     * Delete a document via Frappe REST API.
     *
     * @see https://docs.frappe.io/framework/user/en/api/rest#delete
     */
    public function erpDelete(string $doctype, string $name, bool $systemGenerated = false): array
    {
        return $this->erpOperation('delete', $doctype, $name, [], $systemGenerated);
    }

    /**
     * Submit a document in ERPNext/Frappe (e.g. Stock Entry) so that it runs
     * server-side submit logic (docstatus = 1) and creates ledger entries (e.g. Stock Ledger Entry).
     * Uses Frappe method API: POST /api/method/frappe.client.submit
     * On 401, ErpClient throws ErpAuthenticationException and execution stops.
     *
     * @param  string  $doctype  Frappe DocType (e.g. "Stock Entry")
     * @param  string  $name  Document name
     * @param  bool  $systemGenerated  Use system API key
     * @return array Response with 'message' on success; 'exception' or 'exc' on error
     */
    public function erpSubmitDocument(string $doctype, string $name, bool $systemGenerated = true): array
    {
        $url = $this->getErpBaseUrl().'/api/method/frappe.client.submit';
        $payload = ['doc' => ['doctype' => $doctype, 'name' => $name]];

        try {
            $client = app(ErpClient::class);
            $result = $client->request('POST', $url, [
                'headers' => $this->getErpHeaders($systemGenerated),
                'body' => $payload,
            ]);
            $this->logErpErrorIfPresent($result, $url, 'POST');

            return $result;
        } catch (ErpAuthenticationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('ERP submit document error', [
                'doctype' => $doctype,
                'name' => $name,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = $this->isErpConnectionError($e->getMessage())
                ? self::erpConnectionUnavailableMessage()
                : $e->getMessage();

            return ['exception' => $message, 'error' => 1];
        }
    }

    /**
     * Execute create or update based on method (post/put).
     * Use when method is determined at runtime.
     *
     * @param  string  $method  'post' or 'put'
     * @param  string  $doctype  Frappe DocType
     * @param  string|null  $name  Document name for put; null for post
     * @param  array  $data  Request body
     * @param  bool  $systemGenerated  Use system API key
     * @return array Frappe response
     */
    public function erpCall(string $method, string $doctype, ?string $name, array $data, bool $systemGenerated = false): array
    {
        return strtolower($method) === 'post'
            ? $this->erpPost($doctype, $data, $systemGenerated)
            : $this->erpPut($doctype, $name ?? '', $data, $systemGenerated);
    }

    /**
     * Log Frappe API errors (exception, exc, exc_type keys).
     */
    private function logErpErrorIfPresent(array $result, string $url, string $method): void
    {
        $hasError = Arr::has($result, 'exception')
            || Arr::has($result, 'exc')
            || Arr::has($result, 'exc_type');

        if ($hasError) {
            Log::warning('ERP exception', [
                'url' => $url,
                'method' => $method,
                'exception' => $result['exception'] ?? $result['exc'] ?? null,
                'exc_type' => $result['exc_type'] ?? null,
                'message' => $result['message'] ?? null,
            ]);
        }
    }

    public function revertChanges($stateBeforeUpdate)
    {
        DB::connection('mysql')->beginTransaction();
        try {
            foreach ($stateBeforeUpdate as $doctype => $values) {
                foreach ($values as $id => $value) {
                    if (! is_array($value) && ! is_object($value)) {
                        DB::connection('mysql')->table("tab$doctype")->where('name', $id)->delete();
                    } else {
                        $value = collect($value)->except(['name', 'owner', 'creation', 'docstatus', 'doctype', 'parent', 'parentfield', 'parenttype'])->toArray();
                        DB::connection('mysql')->table("tab$doctype")->where('name', $id)->update($value);
                    }
                }
            }

            DB::connection('mysql')->commit();

            return 1;
        } catch (\Throwable $th) {
            Log::error('ERP revertChanges failed', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            DB::connection('mysql')->rollBack();

            return 0;
        }
    }

    public function generateRandomString($length = 15)
    {
        return \Illuminate\Support\Str::random($length);
    }

    /**
     * Generate ERP API credentials for a user. Pass $user when called during login
     * (Auth::user() is not yet set); otherwise the authenticated user is used.
     *
     * @param  \App\Models\User|null  $user  User to generate credentials for; defaults to Auth::user()
     */
    public function generateApiCredentials(?\App\Models\User $user = null)
    {
        $targetUser = $user ?? Auth::user();
        if (! $targetUser) {
            return ['success' => 0, 'message' => 'No user available to generate API credentials.'];
        }

        try {
            $loggedInUser = str_replace('fumaco.local', 'fumaco.com', $targetUser->wh_user);
            $existingKey = $this->erpGet('User', $loggedInUser, [], true);
            if (! Arr::has($existingKey, 'data')) { // Promodisers
                $loggedInUser = $targetUser->wh_user;
                $existingKey = $this->erpGet('User', $loggedInUser, [], true);
            }
            $existingKey = data_get($existingKey, 'data.api_key');

            $tokens = [
                'api_key' => $existingKey ?? $this->generateRandomString(),
                'api_secret' => $this->generateRandomString(),
            ];

            $erpUser = $this->erpPut('User', $loggedInUser, $tokens, true);

            if (! Arr::has($erpUser, 'data')) {
                $error = $this->erpErrorMessage($erpUser, 'An error occured while generating API tokens');
                throw new \Exception($error);
            }

            $warehouseUser = $this->erpPut('Warehouse Users', $targetUser->name, $tokens, true);

            if (! Arr::has($warehouseUser, 'data')) {
                $error = $this->erpErrorMessage($warehouseUser, 'An error occured while generating API tokens');
                throw new \Exception($error);
            }

            $targetUser->api_key = $tokens['api_key'];
            $targetUser->api_secret = $tokens['api_secret'];
            $targetUser->save();

            return ['success' => 1, 'message' => 'API Credentials Created!'];
        } catch (\Exception $th) {
            Log::error('ERP generateApiCredentials failed', [
                'user' => $targetUser->wh_user ?? null,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            $userMessage = $this->userFriendlyErpMessage($th->getMessage());

            return ['success' => 0, 'message' => $userMessage];
        }
    }

    /**
     * Get error message from ERP response (exception, message from erpOperation catch, or default).
     */
    private function erpErrorMessage(array $response, string $default): string
    {
        if (Arr::has($response, 'exception')) {
            return (string) data_get($response, 'exception');
        }
        if (Arr::get($response, 'error') && Arr::has($response, 'message')) {
            return (string) data_get($response, 'message');
        }

        return $default;
    }

    /**
     * Convert ERP/HTTP error messages to user-friendly text for login and API credential flows.
     */
    private function userFriendlyErpMessage(string $technicalMessage): string
    {
        $lower = strtolower($technicalMessage);
        if (str_contains($lower, 'timed out') || str_contains($lower, 'cURL error 28')) {
            return 'The ERP service did not respond in time. Please try again later or contact support.';
        }
        if (str_contains($lower, 'connection refused') || str_contains($lower, 'could not resolve host')
            || str_contains($lower, 'cURL error 6') || str_contains($lower, 'cURL error 7')) {
            return 'The ERP service is temporarily unreachable. Please try again later or contact support.';
        }

        return $technicalMessage;
    }
}
