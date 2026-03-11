<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Test ERP (Frappe) API credentials from config.
 * Run after changing .env to verify the app can authenticate.
 *
 * Usage: php artisan erp:test-connection
 */
class TestErpConnection extends Command
{
    protected $signature = 'erp:test-connection';

    protected $description = 'Test ERP API connection using config credentials (ERP_API_KEY, ERP_API_SECRET_KEY)';

    public function handle(): int
    {
        $baseUrl = rtrim(config('erp.api_base_url') ?? config('services.erp.api_base_url') ?? '', '/');
        $apiKey = config('erp.api_key') ?? config('services.erp.api_key');
        $apiSecret = config('erp.api_secret_key') ?? config('services.erp.api_secret_key');

        if (! $baseUrl || ! $apiKey || ! $apiSecret) {
            $this->error('Missing config: set ERP_API_BASE_URL, ERP_API_KEY, ERP_API_SECRET_KEY in .env');
            $this->line('Then run: php artisan config:clear');
            return self::FAILURE;
        }

        $url = $baseUrl.'/api/resource/User?limit_page_length=1';
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'token '.$apiKey.':'.$apiSecret,
        ];

        $this->line('Calling ERP at: '.$baseUrl.' ...');

        $response = Http::withHeaders($headers)->get($url);

        if ($response->successful()) {
            $this->info('ERP connection OK. Credentials are valid.');
            return self::SUCCESS;
        }

        if ($response->status() === 401) {
            $this->error('ERP returned 401 Unauthorized.');
            $this->line('The API key/secret in config are rejected by Frappe.');
            $this->newLine();
            $this->line('Fix:');
            $this->line('  1. In Frappe: Setup → User → [your API user] → API Access → Generate Keys');
            $this->line('  2. Copy the new API Key and API Secret into .env as ERP_API_KEY and ERP_API_SECRET_KEY');
            $this->line('  3. Run: php artisan config:clear');
            $this->line('  4. Restart the app container and run this command again.');
            return self::FAILURE;
        }

        $this->error('ERP returned HTTP '.$response->status().': '.$response->body());
        return self::FAILURE;
    }
}
