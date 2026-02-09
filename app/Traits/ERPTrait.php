<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
     * Get headers for Frappe REST API requests.
     * Uses token-based auth: "token api_key:api_secret" per Frappe docs.
     */
    public function getErpHeaders(bool $systemGenerated = true): array
    {
        if ($systemGenerated) {
            $apiKey = config('services.erp.api_key');
            $apiSecret = config('services.erp.api_secret_key');
        } else {
            $apiKey = Auth::user()->api_key;
            $apiSecret = Auth::user()->api_secret;
        }

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'token ' . $apiKey . ':' . $apiSecret,
            'Accept-Language' => 'en',
        ];
    }

    /**
     * Execute Frappe REST API operation.
     *
     * @param  string  $method  GET, POST, PUT, DELETE
     * @param  string  $doctype  Frappe DocType (e.g. "Stock Entry", "Bin")
     * @param  string|null  $name  Document name for read/update/delete
     * @param  array  $payload  For GET: query params (fields, filters, etc). For POST/PUT: request body
     * @param  bool  $systemGenerated  Use system API key vs logged-in user's key
     * @return array  Frappe response with 'data' key; 'exception' or 'exc' on error
     */
    private function erpOperation($method, $doctype, $name = null, $payload = [], $systemGenerated = false)
    {
        try {
            $erpApiBaseUrl = config('services.erp.api_base_url');
            $url = rtrim("$erpApiBaseUrl/api/resource/$doctype", '/');
            if ($name) {
                $url .= '/' . $name;
            }

            $http = Http::withHeaders($this->getErpHeaders($systemGenerated));

            $response = match (strtoupper($method)) {
                'GET' => $http->get($url, $payload),
                'POST' => $http->post($url, $payload),
                'PUT' => $http->put($url, $payload),
                'DELETE' => $http->delete($url),
                default => throw new \InvalidArgumentException("Invalid HTTP method: $method"),
            };

            if ($response->failed()) {
                Log::warning('ERP request failed', [
                    'url' => $url,
                    'method' => $method,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

            $result = $response->json() ?? [];
            $this->logErpErrorIfPresent($result, $url, $method);

            return $result;
        } catch (\Throwable $th) {
            Log::error('ERP request error', [
                'method' => $method,
                'doctype' => $doctype,
                'name' => $name,
                'message' => $th->getMessage(),
            ]);

            return ['error' => 1, 'message' => $th->getMessage()];
        }
    }

    /**
     * List documents from Frappe REST API.
     *
     * @see https://docs.frappe.io/framework/user/en/api/rest#listing-documents
     *
     * @param  string  $doctype  Frappe DocType
     * @param  array  $params  Query params: fields, filters, or_filters, order_by, limit_start, limit_page_length
     * @param  bool  $systemGenerated  Use system API key
     * @return array  Response with 'data' array of records
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
     * Execute create or update based on method (post/put).
     * Use when method is determined at runtime.
     *
     * @param  string  $method  'post' or 'put'
     * @param  string  $doctype  Frappe DocType
     * @param  string|null  $name  Document name for put; null for post
     * @param  array  $data  Request body
     * @param  bool  $systemGenerated  Use system API key
     * @return array  Frappe response
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
        $hasError = array_key_exists('exception', $result)
            || array_key_exists('exc', $result)
            || array_key_exists('exc_type', $result);

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

    public function revertChanges($stateBeforeUpdate){
        DB::connection('mysql')->beginTransaction();
        try {
            foreach ($stateBeforeUpdate as $doctype => $values) {
                foreach($values as $id => $value){
                    if(!is_array($value) && !is_object($value)){
                        DB::connection('mysql')->table("tab$doctype")->where('name', $id)->delete();
                    }else{
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

    public function generateRandomString($length = 15) {
        return \Illuminate\Support\Str::random($length);
    }

    public function generateApiCredentials()
    {
        try {
            $loggedInUser = str_replace('fumaco.local', 'fumaco.com', Auth::user()->wh_user);
            $existingKey = $this->erpGet('User', $loggedInUser, [], true);
            if (!array_key_exists('data', $existingKey)) { // Promodisers
                $loggedInUser = Auth::user()->wh_user;
                $existingKey = $this->erpGet('User', $loggedInUser, [], true);
            }
            $existingKey = data_get($existingKey, 'data.api_key');

            $tokens = [
                'api_key' => $existingKey ?? $this->generateRandomString(),
                'api_secret' => $this->generateRandomString()
            ];

            $user = $this->erpPut('User', $loggedInUser, $tokens, true);

            if (!array_key_exists('data', $user)) {
                $error = data_get($user, 'exception', 'An error occured while generating API tokens');
                throw new \Exception($error);
            }

            $warehouseUser = $this->erpPut('Warehouse Users', Auth::user()->name, $tokens, true);

            if(!array_key_exists('data', $warehouseUser)){
                $error = data_get($warehouseUser, 'exception', 'An error occured while generating API tokens');
                throw new \Exception($error);
            }

            $user = Auth::user();
            $user->api_key = $tokens['api_key'];
            $user->api_secret = $tokens['api_secret'];
            $user->save();
            
            return ['success' => 1, 'message' => 'API Credentials Created!'];
        } catch (\Exception $th) {
            Log::error('ERP generateApiCredentials failed', [
                'user' => Auth::user()?->wh_user,
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
            return ['success' => 0, 'message' => $th->getMessage()];
        }
    }
}