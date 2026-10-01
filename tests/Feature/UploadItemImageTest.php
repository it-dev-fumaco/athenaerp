<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadItemImageTest extends TestCase
{
    private const ITEM_CODE = 'ITEM-UPLOAD-001';

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
        DB::purge('mysql');

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
                modified_by TEXT,
                creation TEXT,
                owner TEXT,
                parentfield TEXT,
                parenttype TEXT
            )'
        );

        DB::connection('mysql')->table('tabItem')->insert([
            'name' => self::ITEM_CODE,
            'image' => null,
            'modified' => now()->toDateTimeString(),
            'modified_by' => 'Administrator',
        ]);

        Storage::fake('upcloud');
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

    public function test_upload_stores_jpeg_sidecar_for_erpnext(): void
    {
        $user = $this->createUser();
        $file = UploadedFile::fake()->image('photo.jpg', 20, 20);

        $response = $this->actingAs($user)->post('/upload_item_image', [
            'item_code' => self::ITEM_CODE,
            'item_image' => [$file],
        ]);

        $response->assertOk();

        $imgFiles = collect(Storage::disk('upcloud')->allFiles('img'))
            ->reject(fn (string $path) => str_starts_with($path, 'img/thumbs/'))
            ->values();

        $this->assertTrue(
            $imgFiles->contains(fn (string $path) => str_ends_with($path, '.jpg')),
            'Expected a JPEG object on the upcloud disk. Files: '.$imgFiles->implode(', ')
        );

        $jpegKey = $imgFiles->first(fn (string $path) => str_ends_with($path, '.jpg'));
        $this->assertNotNull($jpegKey);
        $this->assertSame('public', Storage::disk('upcloud')->getVisibility($jpegKey));

        $itemImage = DB::connection('mysql')->table('tabItem')->where('name', self::ITEM_CODE)->value('image');
        $this->assertNotEmpty($itemImage);
        $this->assertStringContainsString('.jpg', (string) $itemImage);
        $this->assertStringNotContainsString('X-Amz-', (string) $itemImage);
    }
}
