<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        Storage::fake('upcloud');
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
            'CREATE TABLE IF NOT EXISTS "tabItem" (
                name TEXT PRIMARY KEY,
                image TEXT,
                modified TEXT,
                modified_by TEXT
            )'
        );
        $conn->getPdo()->exec(
            'CREATE TABLE IF NOT EXISTS "tabItem Images" (
                name TEXT PRIMARY KEY,
                parent TEXT,
                image_path TEXT,
                public_url TEXT,
                idx INTEGER,
                modified TEXT,
                modified_by TEXT
            )'
        );
    }

    private function seedImages(): void
    {
        DB::connection('mysql')->table('tabItem')->insert([
            [
                'name' => self::ITEM_CODE,
                'image' => '/files/old-erpnext-image.png',
                'modified' => now()->toDateTimeString(),
                'modified_by' => 'Administrator',
            ],
        ]);

        DB::connection('mysql')->table('tabItem Images')->insert([
            [
                'name' => 'img-1',
                'parent' => self::ITEM_CODE,
                'image_path' => 'img/one.webp',
                'public_url' => null,
                'idx' => 1,
            ],
            [
                'name' => 'img-2',
                'parent' => self::ITEM_CODE,
                'image_path' => 'img/two.webp',
                'public_url' => null,
                'idx' => 2,
            ],
            [
                'name' => 'img-3',
                'parent' => self::ITEM_CODE,
                'image_path' => 'img/three.webp',
                'public_url' => null,
                'idx' => 3,
            ],
            [
                'name' => 'img-other',
                'parent' => 'ITEM-OTHER',
                'image_path' => 'img/other.webp',
                'public_url' => null,
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
        Storage::disk('upcloud')->put('img/one.webp', 'one', 'private');
        Storage::disk('upcloud')->put('img/two.webp', 'two', 'private');
        Storage::disk('upcloud')->put('img/three.webp', 'three', 'private');

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

        $this->assertSame('public', Storage::disk('upcloud')->getVisibility('img/three.webp'));

        $expectedUrl = Storage::disk('upcloud')->url('img/three.webp');
        $this->assertNotEmpty($expectedUrl);
        $this->assertStringNotContainsString('X-Amz-', $expectedUrl);
        $this->assertStringNotContainsString('Expires=', $expectedUrl);

        $this->assertSame(
            $expectedUrl,
            DB::connection('mysql')->table('tabItem Images')->where('name', 'img-3')->value('public_url')
        );
        $this->assertSame(
            $expectedUrl,
            DB::connection('mysql')->table('tabItem')->where('name', self::ITEM_CODE)->value('image')
        );
        $this->assertNull(
            DB::connection('mysql')->table('tabItem Images')->where('name', 'img-1')->value('public_url')
        );
    }

    public function test_erpnext_url_prefers_jpeg_when_jpeg_and_webp_exist(): void
    {
        Storage::disk('upcloud')->put('img/three.jpg', 'jpeg-bytes', 'private');
        Storage::disk('upcloud')->put('img/three.webp', 'webp-bytes', 'private');

        $user = $this->createUser();

        $this->actingAs($user)->postJson('/set_default_item_image', [
            'item_code' => self::ITEM_CODE,
            'image_name' => 'img-3',
        ])->assertOk();

        $expectedUrl = Storage::disk('upcloud')->url('img/three.jpg');
        $this->assertStringEndsWith('.jpg', parse_url($expectedUrl, PHP_URL_PATH) ?? $expectedUrl);
        $this->assertSame(
            $expectedUrl,
            DB::connection('mysql')->table('tabItem')->where('name', self::ITEM_CODE)->value('image')
        );
        $this->assertSame(
            $expectedUrl,
            DB::connection('mysql')->table('tabItem Images')->where('name', 'img-3')->value('public_url')
        );
        $this->assertSame('public', Storage::disk('upcloud')->getVisibility('img/three.jpg'));
    }

    public function test_converts_webp_default_to_jpeg_for_erpnext(): void
    {
        if (! function_exists('imagejpeg') || ! function_exists('imagecreatetruecolor') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD WebP/JPEG is required.');
        }

        $gd = imagecreatetruecolor(4, 4);
        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'athena-webp-'.uniqid('', true).'.webp';
        $ok = @imagewebp($gd, $tmp, 80);
        imagedestroy($gd);
        if (! $ok || ! is_file($tmp)) {
            $this->markTestSkipped('Could not encode a WebP test image.');
        }

        Storage::disk('upcloud')->put('img/three.webp', (string) file_get_contents($tmp), 'private');
        @unlink($tmp);

        $decoded = @imagecreatefromstring((string) Storage::disk('upcloud')->get('img/three.webp'));
        if ($decoded === false) {
            $this->markTestSkipped('GD cannot decode WebP.');
        }
        imagedestroy($decoded);

        $user = $this->createUser();
        $this->actingAs($user)->postJson('/set_default_item_image', [
            'item_code' => self::ITEM_CODE,
            'image_name' => 'img-3',
        ])->assertOk();

        $this->assertTrue(Storage::disk('upcloud')->exists('img/three.jpg'));
        $expectedUrl = Storage::disk('upcloud')->url('img/three.jpg');
        $this->assertSame(
            $expectedUrl,
            DB::connection('mysql')->table('tabItem')->where('name', self::ITEM_CODE)->value('image')
        );
        $this->assertSame(
            $expectedUrl,
            DB::connection('mysql')->table('tabItem Images')->where('name', 'img-3')->value('public_url')
        );
    }

    public function test_erpnext_url_falls_back_to_webp_when_jpeg_is_missing(): void
    {
        Storage::disk('upcloud')->put('img/three.webp', 'webp-bytes', 'private');

        $user = $this->createUser();

        $this->actingAs($user)->postJson('/set_default_item_image', [
            'item_code' => self::ITEM_CODE,
            'image_name' => 'img-3',
        ])->assertOk();

        $expectedUrl = Storage::disk('upcloud')->url('img/three.webp');
        $this->assertStringEndsWith('.webp', parse_url($expectedUrl, PHP_URL_PATH) ?? $expectedUrl);
        $this->assertSame(
            $expectedUrl,
            DB::connection('mysql')->table('tabItem')->where('name', self::ITEM_CODE)->value('image')
        );
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
