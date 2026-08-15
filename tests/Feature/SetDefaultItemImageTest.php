<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SetDefaultItemImageTest extends TestCase
{
    private const ITEM_CODE = 'ITEM-IMG-001';

    protected function setUp(): void
    {
        parent::setUp();

        $this->useMysqlAsSqlite();
        $this->createSchema();
        $this->seedImages();
    }

    private function useMysqlAsSqlite(): void
    {
        Config::set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        Config::set('database.default', 'mysql');
        DB::purge('mysql');
    }

    private function createSchema(): void
    {
        $conn = DB::connection('mysql');
        $conn->getPdo()->exec(
            'CREATE TABLE IF NOT EXISTS "tabWarehouse Users" (
                name TEXT PRIMARY KEY,
                wh_user TEXT,
                frappe_userid TEXT,
                full_name TEXT,
                email TEXT,
                password TEXT,
                created_at TEXT,
                updated_at TEXT
            )'
        );
        $conn->getPdo()->exec(
            'CREATE TABLE IF NOT EXISTS "tabItem Images" (
                name TEXT PRIMARY KEY,
                parent TEXT,
                image_path TEXT,
                idx INTEGER
            )'
        );
    }

    private function seedImages(): void
    {
        DB::connection('mysql')->table('tabItem Images')->insert([
            [
                'name' => 'img-1',
                'parent' => self::ITEM_CODE,
                'image_path' => 'img/one.webp',
                'idx' => 1,
            ],
            [
                'name' => 'img-2',
                'parent' => self::ITEM_CODE,
                'image_path' => 'img/two.webp',
                'idx' => 2,
            ],
            [
                'name' => 'img-3',
                'parent' => self::ITEM_CODE,
                'image_path' => 'img/three.webp',
                'idx' => 3,
            ],
            [
                'name' => 'img-other',
                'parent' => 'ITEM-OTHER',
                'image_path' => 'img/other.webp',
                'idx' => 1,
            ],
        ]);
    }

    private function createUser(): User
    {
        DB::connection('mysql')->table('tabWarehouse Users')->insert([
            'name' => 'test-user-1',
            'wh_user' => 'wh_test',
            'frappe_userid' => 'frappe_test@test.com',
            'full_name' => 'Test User',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return User::find('test-user-1');
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->post('/set_default_item_image', [
            'item_code' => self::ITEM_CODE,
            'image_name' => 'img-3',
        ])->assertRedirect(route('login'));
    }

    public function test_promotes_chosen_image_to_idx_one_and_keeps_relative_order(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->postJson('/set_default_item_image', [
            'item_code' => self::ITEM_CODE,
            'image_name' => 'img-3',
        ]);

        $response->assertOk();
        $response->assertJsonPath('default_image_name', 'img-3');

        $indexes = DB::connection('mysql')
            ->table('tabItem Images')
            ->where('parent', self::ITEM_CODE)
            ->pluck('idx', 'name');

        $this->assertSame(1, (int) $indexes['img-3']);
        $this->assertSame(2, (int) $indexes['img-1']);
        $this->assertSame(3, (int) $indexes['img-2']);
        $this->assertSame(1, (int) DB::connection('mysql')->table('tabItem Images')->where('name', 'img-other')->value('idx'));
    }

    public function test_returns_422_when_image_does_not_belong_to_item(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->postJson('/set_default_item_image', [
            'item_code' => self::ITEM_CODE,
            'image_name' => 'img-other',
        ])->assertStatus(422);
    }
}
