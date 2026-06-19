<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserLoginActivityLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        Config::set('database.default', 'mysql');
        Config::set('login_activity.allowed_user_groups', ['Director', 'Inventory Manager']);
        DB::purge('mysql');

        $pdo = DB::connection('mysql')->getPdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS "tabWarehouse Users" (
            name TEXT PRIMARY KEY,
            wh_user TEXT,
            frappe_userid TEXT,
            full_name TEXT,
            user_group TEXT,
            password TEXT,
            api_key TEXT,
            api_secret TEXT,
            last_login TEXT
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS "tabUser Activity Login" (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id TEXT,
            username TEXT NOT NULL,
            login_at TEXT NOT NULL,
            ip_address TEXT,
            user_agent TEXT,
            status TEXT NOT NULL
        )');
    }

    private function insertUser(string $name, string $userGroup): void
    {
        DB::connection('mysql')->table('tabWarehouse Users')->insert([
            'name' => $name,
            'wh_user' => $name.'@fumaco.com',
            'frappe_userid' => $name.'@fumaco.com',
            'full_name' => 'Test '.$name,
            'user_group' => $userGroup,
            'password' => bcrypt('password'),
        ]);
    }

    public function test_failed_password_login_writes_activity_row(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->post('/login_user', [
            'email' => 'nonexistent@fumaco.com',
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('tabUser Activity Login', [
            'status' => 'failed',
            'username' => 'nonexistent@fumaco.com',
        ], 'mysql');

        $row = DB::connection('mysql')->table('tabUser Activity Login')->first();
        $this->assertNotNull($row);
        $this->assertNull($row->user_id);
    }

    public function test_login_activity_logs_endpoint_forbidden_for_disallowed_group(): void
    {
        $this->insertUser('u-restricted', 'User');
        $user = User::find('u-restricted');

        $response = $this->actingAs($user)->getJson('/admin/login-activity/logs');

        $response->assertForbidden();
    }

    public function test_login_activity_logs_endpoint_returns_paginated_json_for_allowed_group(): void
    {
        $this->insertUser('u-director', 'Director');
        $user = User::find('u-director');

        DB::connection('mysql')->table('tabUser Activity Login')->insert([
            'user_id' => 'u-director',
            'username' => 'u-director@fumaco.com',
            'login_at' => now()->toDateTimeString(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'status' => 'success',
        ]);

        $response = $this->actingAs($user)->getJson('/admin/login-activity/logs?per_page=10');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'per_page',
            'total',
        ]);
        $response->assertJsonPath('data.0.status', 'success');
    }
}
