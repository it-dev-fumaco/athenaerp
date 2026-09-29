<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackfillErpItemImagesCommandTest extends TestCase
{
    private const ITEM_CODE = 'EB00026';

    private const FILENAME = '167703253634-EB00026-A.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        $this->useMysqlAsSqlite();
        $this->createSchema();
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

    private function seedItem(string $itemImage, string $imagePath, int $idx = 1): void
    {
        DB::connection('mysql')->table('tabItem')->insert([
            'name' => self::ITEM_CODE,
            'image' => $itemImage,
            'modified' => now()->toDateTimeString(),
            'modified_by' => 'Administrator',
        ]);

        DB::connection('mysql')->table('tabItem Images')->insert([
            'name' => 'img-default',
            'parent' => self::ITEM_CODE,
            'image_path' => $imagePath,
            'public_url' => null,
            'idx' => $idx,
        ]);
    }

    public function test_backfill_writes_img_prefixed_url_for_filename_only_path(): void
    {
        $this->seedItem('/files/old-erpnext-image.png', self::FILENAME);
        Storage::disk('upcloud')->put('img/'.self::FILENAME, 'bytes', 'private');

        $exit = Artisan::call('items:backfill-erp-images', [
            '--item' => [self::ITEM_CODE],
        ]);

        $this->assertSame(0, $exit);

        $expectedUrl = Storage::disk('upcloud')->url('img/'.self::FILENAME);
        $this->assertStringNotContainsString('X-Amz-', $expectedUrl);
        $this->assertStringNotContainsString('Expires=', $expectedUrl);
        $this->assertSame(
            $expectedUrl,
            DB::connection('mysql')->table('tabItem')->where('name', self::ITEM_CODE)->value('image')
        );
        $this->assertSame(
            $expectedUrl,
            DB::connection('mysql')->table('tabItem Images')->where('name', 'img-default')->value('public_url')
        );
        $this->assertSame('public', Storage::disk('upcloud')->getVisibility('img/'.self::FILENAME));
    }

    public function test_dry_run_does_not_change_tab_item_image(): void
    {
        $this->seedItem('/files/old-erpnext-image.png', self::FILENAME);
        Storage::disk('upcloud')->put('img/'.self::FILENAME, 'bytes', 'private');

        $exit = Artisan::call('items:backfill-erp-images', [
            '--item' => [self::ITEM_CODE],
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame(
            '/files/old-erpnext-image.png',
            DB::connection('mysql')->table('tabItem')->where('name', self::ITEM_CODE)->value('image')
        );
        $this->assertNull(
            DB::connection('mysql')->table('tabItem Images')->where('name', 'img-default')->value('public_url')
        );
        $this->assertSame('private', Storage::disk('upcloud')->getVisibility('img/'.self::FILENAME));
    }

    public function test_skips_already_backfilled_url_unless_force(): void
    {
        $existingUrl = 'https://rtlmo.upcloudobjects.com/athena-images-files/img/already.webp';
        $this->seedItem($existingUrl, self::FILENAME);
        Storage::disk('upcloud')->put('img/'.self::FILENAME, 'bytes', 'private');

        $exit = Artisan::call('items:backfill-erp-images', [
            '--item' => [self::ITEM_CODE],
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame(
            $existingUrl,
            DB::connection('mysql')->table('tabItem')->where('name', self::ITEM_CODE)->value('image')
        );

        $exit = Artisan::call('items:backfill-erp-images', [
            '--item' => [self::ITEM_CODE],
            '--force' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame(
            Storage::disk('upcloud')->url('img/'.self::FILENAME),
            DB::connection('mysql')->table('tabItem')->where('name', self::ITEM_CODE)->value('image')
        );
    }

    public function test_backfill_prefers_jpeg_when_jpeg_and_webp_exist(): void
    {
        $this->seedItem('/files/old-erpnext-image.png', self::FILENAME);
        Storage::disk('upcloud')->put('img/167703253634-EB00026-A.jpg', 'jpeg', 'private');
        Storage::disk('upcloud')->put('img/167703253634-EB00026-A.webp', 'webp', 'private');

        $exit = Artisan::call('items:backfill-erp-images', [
            '--item' => [self::ITEM_CODE],
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame(
            Storage::disk('upcloud')->url('img/167703253634-EB00026-A.jpg'),
            DB::connection('mysql')->table('tabItem')->where('name', self::ITEM_CODE)->value('image')
        );
    }

    public function test_backfill_falls_back_to_webp_when_jpeg_is_missing(): void
    {
        $this->seedItem('/files/old.png', 'img/only.webp');
        Storage::disk('upcloud')->put('img/only.webp', 'webp', 'private');

        $exit = Artisan::call('items:backfill-erp-images', [
            '--item' => [self::ITEM_CODE],
        ]);

        $this->assertSame(0, $exit);
        $this->assertSame(
            Storage::disk('upcloud')->url('img/only.webp'),
            DB::connection('mysql')->table('tabItem')->where('name', self::ITEM_CODE)->value('image')
        );
    }
}
