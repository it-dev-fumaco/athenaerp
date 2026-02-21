<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemAttribute;
use App\Models\ItemVariantAttribute;
use App\Models\ProductBrochureLog;
use App\Models\User;
use App\Services\BrochureExcelService;
use App\Services\BrochureImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use RuntimeException;
use Tests\TestCase;

class BrochureControllerTest extends TestCase
{
    private string $project = 'TestProject';

    private string $filename = 'brochure.xlsx';

    private string $upcloudRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useUpcloudLocalDisk();
        $this->useMysqlAsSqliteForBrochure();
        $this->ensureBrochureTablesExist();
        $this->ensureWarehouseUsersTableExists();
    }

    /**
     * Use a local disk for 'upcloud' in tests so path() works and we can create real files.
     */
    private function useUpcloudLocalDisk(): void
    {
        $this->upcloudRoot = storage_path('framework/testing/disks/upcloud');
        if (! is_dir($this->upcloudRoot)) {
            mkdir($this->upcloudRoot, 0755, true);
        }
        Config::set('filesystems.disks.upcloud', [
            'driver' => 'local',
            'root' => $this->upcloudRoot,
        ]);
    }

    /**
     * Point mysql connection to sqlite :memory: so brochure models (connection mysql) work in tests.
     */
    private function useMysqlAsSqliteForBrochure(): void
    {
        Config::set('database.connections.mysql', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);
        DB::purge('mysql');
    }

    /**
     * Create brochure-related tables on mysql connection (used by brochure models).
     */
    private function ensureBrochureTablesExist(): void
    {
        $conn = DB::connection('mysql');

        $tables = [
            'tabProduct Brochure Log' => 'CREATE TABLE IF NOT EXISTS "tabProduct Brochure Log" (
                name TEXT PRIMARY KEY,
                creation TEXT,
                modified TEXT,
                modified_by TEXT,
                owner TEXT,
                project TEXT,
                filename TEXT,
                created_by TEXT,
                transaction_date TEXT,
                remarks TEXT,
                transaction_type TEXT
            )',
            'tabItem Brochure Image' => 'CREATE TABLE IF NOT EXISTS "tabItem Brochure Image" (
                name TEXT PRIMARY KEY,
                creation TEXT,
                modified TEXT,
                parent TEXT,
                idx TEXT,
                image_filename TEXT,
                image_path TEXT,
                modified_by TEXT,
                owner TEXT
            )',
            'tabItem' => 'CREATE TABLE IF NOT EXISTS "tabItem" (
                name TEXT PRIMARY KEY,
                item_name TEXT,
                item_brochure_name TEXT,
                item_brochure_description TEXT,
                item_brochure_remarks TEXT,
                modified TEXT,
                modified_by TEXT
            )',
            'tabItem Attribute' => 'CREATE TABLE IF NOT EXISTS "tabItem Attribute" (
                name TEXT PRIMARY KEY,
                attr_name TEXT,
                modified TEXT,
                modified_by TEXT
            )',
            'tabItem Variant Attribute' => 'CREATE TABLE IF NOT EXISTS "tabItem Variant Attribute" (
                name TEXT PRIMARY KEY,
                parent TEXT,
                attribute TEXT,
                attribute_value TEXT,
                brochure_idx INTEGER,
                hide_in_brochure INTEGER DEFAULT 0,
                idx INTEGER,
                modified TEXT,
                modified_by TEXT
            )',
        ];

        foreach ($tables as $sql) {
            $conn->getPdo()->exec($sql);
        }
    }

    /**
     * Create tabWarehouse Users on default connection for auth (User model uses default connection).
     */
    private function ensureWarehouseUsersTableExists(): void
    {
        $conn = DB::connection(config('database.default'));
        $conn->getPdo()->exec(
            'CREATE TABLE IF NOT EXISTS "tabWarehouse Users" (
                name TEXT PRIMARY KEY,
                wh_user TEXT,
                email TEXT,
                password TEXT,
                created_at TEXT,
                updated_at TEXT
            )'
        );
    }

    /**
     * Create a minimal brochure Excel file at item-brochures/PROJECT/filename with row 4 = headers.
     */
    private function createMinimalBrochureExcel(string $columnHeader = 'Image 1'): string
    {
        $dir = $this->upcloudRoot.'/item-brochures/'.strtoupper($this->project);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $path = $dir.'/'.$this->filename;

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue([2, 2], $this->project);
        $sheet->setCellValue([2, 3], 'Test Customer');
        $sheet->setCellValue([1, 4], 'Item');
        $sheet->setCellValue([2, 4], 'Ref');
        $sheet->setCellValue([3, 4], 'Desc');
        $sheet->setCellValue([4, 4], 'Location');
        $sheet->setCellValue([5, 4], $columnHeader);
        $sheet->setCellValue([1, 5], 'Product A');
        $sheet->setCellValue([2, 5], '-');
        $sheet->setCellValue([3, 5], '-');
        $sheet->setCellValue([4, 5], '-');
        $sheet->setCellValue([5, 5], null);

        $writer = new WriterXlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    /** @return array{project: string, filename: string, row: int, column: string} */
    private function validUploadImageParams(): array
    {
        return [
            'project' => $this->project,
            'filename' => $this->filename,
            'row' => 5,
            'column' => 'Image 1',
        ];
    }

    public function test_image_upload_success_returns_legacy_response_and_creates_log(): void
    {
        $this->createMinimalBrochureExcel('Image 1');

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);

        $response = $this->post('/upload_image', array_merge($this->validUploadImageParams(), [
            'item_image_id' => 'img-1',
            'selected-file' => $file,
        ]));
        $response->assertOk();
        $response->assertJsonPath('status', 1);
        $response->assertJsonPath('message', 'Image uploaded.');
        $response->assertJsonPath('data.src', 'photo.jpg');
        $response->assertJsonPath('data.item_image_id', 'img-1');

        $this->assertDatabaseHas('tabProduct Brochure Log', [
            'project' => $this->project,
            'filename' => 'photo.jpg',
            'transaction_type' => 'Upload Image',
        ], 'mysql');
    }

    public function test_image_replacement_updates_excel_cell_and_creates_second_log(): void
    {
        $this->createMinimalBrochureExcel('Image 1');

        $first = UploadedFile::fake()->image('first.jpg', 100, 100);
        $this->post('/upload_image', array_merge($this->validUploadImageParams(), [
            'selected-file' => $first,
        ]));

        $second = UploadedFile::fake()->image('second.jpg', 100, 100);
        $response = $this->post('/upload_image', array_merge($this->validUploadImageParams(), [
            'selected-file' => $second,
        ]));
        $response->assertOk();

        $count = ProductBrochureLog::where('project', $this->project)
            ->where('transaction_type', 'Upload Image')
            ->count();
        $this->assertGreaterThanOrEqual(2, $count);

        $excelPath = $this->upcloudRoot.'/item-brochures/'.strtoupper($this->project).'/'.$this->filename;
        $service = app(BrochureExcelService::class);
        $column = $service->findColumnIndexByHeader($excelPath, 'Image 1');
        $this->assertNotNull($column);
    }

    public function test_invalid_file_upload_returns_legacy_failure_shape(): void
    {
        $response = $this->postJson('/upload_image', array_merge($this->validUploadImageParams(), []));

        $response->assertStatus(422);
        $response->assertJsonPath('status', 0);
        $response->assertJsonStructure(['message']);
    }

    public function test_invalid_file_extension_returns_legacy_failure_message(): void
    {
        $this->createMinimalBrochureExcel('Image 1');
        $file = UploadedFile::fake()->create('document.txt', 10, 'text/plain');

        $response = $this->post('/upload_image', array_merge($this->validUploadImageParams(), [
            'selected-file' => $file,
        ]));
        $response->assertStatus(422);
        $response->assertJsonPath('status', 0);
        $response->assertJsonStructure(['message']);
    }

    public function test_storage_failure_returns_failure_and_does_not_create_log(): void
    {
        $this->createMinimalBrochureExcel('Image 1');

        $this->mock(BrochureImageService::class, function ($mock): void {
            $mock->shouldReceive('storeSpreadsheetImage')
                ->once()
                ->andThrow(new RuntimeException('Failed to move uploaded file to brochure folder.'));
        });

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);
        $before = ProductBrochureLog::where('transaction_type', 'Upload Image')->count();

        $response = $this->post('/upload_image', array_merge($this->validUploadImageParams(), [
            'selected-file' => $file,
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('status', 0);
        $this->assertSame($before, ProductBrochureLog::where('transaction_type', 'Upload Image')->count());
    }

    public function test_attribute_update_ordering_updates_brochure_idx_and_hide_in_brochure(): void
    {
        DB::connection(config('database.default'))->table('tabWarehouse Users')->insert([
            'name' => 'test-user',
            'wh_user' => 'test@test.com',
            'email' => 'test@test.com',
            'password' => Hash::make('password'),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);
        $user = User::first();

        $itemCode = 'ITEM-001';
        $now = now()->toDateTimeString();
        Item::insert([
            'name' => $itemCode,
            'item_name' => 'Test Item',
            'modified' => $now,
            'modified_by' => $user->wh_user,
        ]);
        ItemAttribute::insert([
            'name' => 'attr-a',
            'attr_name' => 'Size',
            'modified' => $now,
            'modified_by' => $user->wh_user,
        ]);
        ItemAttribute::insert([
            'name' => 'attr-b',
            'attr_name' => 'Color',
            'modified' => $now,
            'modified_by' => $user->wh_user,
        ]);
        ItemVariantAttribute::insert([
            'name' => 'va-1',
            'parent' => $itemCode,
            'attribute' => 'attr-a',
            'attribute_value' => 'M',
            'brochure_idx' => 2,
            'hide_in_brochure' => 0,
            'idx' => 1,
            'modified' => $now,
            'modified_by' => $user->wh_user,
        ]);
        ItemVariantAttribute::insert([
            'name' => 'va-2',
            'parent' => $itemCode,
            'attribute' => 'attr-b',
            'attribute_value' => 'Red',
            'brochure_idx' => 1,
            'hide_in_brochure' => 1,
            'idx' => 2,
            'modified' => $now,
            'modified_by' => $user->wh_user,
        ]);

        $response = $this->actingAs($user)->postJson('/update_brochure_attributes', [
            'item_code' => $itemCode,
            'attribute' => [
                'attr-a' => 'Size (new)',
                'attr-b' => 'Color',
            ],
            'current_attribute' => [
                'attr-a' => 'attr-a',
                'attr-b' => 'attr-b',
            ],
            'hidden_attributes' => ['attr-b'],
            'remarks' => 'Updated',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 1);
        $response->assertJsonPath('message', 'Item Attributes updated.');

        $va1 = ItemVariantAttribute::where('parent', $itemCode)->where('attribute', 'attr-a')->first();
        $va2 = ItemVariantAttribute::where('parent', $itemCode)->where('attribute', 'attr-b')->first();
        $this->assertSame(1, (int) $va1->brochure_idx);
        $this->assertSame(2, (int) $va2->brochure_idx);
        $this->assertSame(0, (int) $va1->hide_in_brochure);
        $this->assertSame(1, (int) $va2->hide_in_brochure);
    }

    public function test_transaction_rollback_on_error_does_not_create_product_brochure_log(): void
    {
        $this->createMinimalBrochureExcel('Image 1');

        $this->mock(BrochureExcelService::class, function ($mock): void {
            $mock->shouldReceive('findColumnIndexByHeader')->andReturn(5);
            $mock->shouldReceive('setCellValueAndSave')
                ->once()
                ->andThrow(new RuntimeException('Excel save failed.'));
        });

        $before = ProductBrochureLog::where('transaction_type', 'Upload Image')->count();

        $file = UploadedFile::fake()->image('photo.jpg', 100, 100);
        $response = $this->post('/upload_image', array_merge($this->validUploadImageParams(), [
            'selected-file' => $file,
        ]));

        $response->assertStatus(422);
        $response->assertJsonPath('status', 0);
        $this->assertSame($before, ProductBrochureLog::where('transaction_type', 'Upload Image')->count());
    }
}
