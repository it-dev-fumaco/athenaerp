<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ItemOrderHistoryTest extends TestCase
{
    private const ITEM_CODE = 'ITEM-OH-001';

    protected function setUp(): void
    {
        parent::setUp();

        $this->useMysqlAsSqlite();
        $this->createSchema();
        $this->seedSalesOrders();
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
        Config::set('erp.web_base_url', 'https://erp.test');
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
                user_group TEXT,
                department TEXT,
                created_at TEXT,
                updated_at TEXT
            )'
        );
        $conn->getPdo()->exec(
            'CREATE TABLE IF NOT EXISTS "tabItem" (
                name TEXT PRIMARY KEY,
                item_code TEXT,
                stock_uom TEXT
            )'
        );
        $conn->getPdo()->exec(
            'CREATE TABLE IF NOT EXISTS "tabSales Order" (
                name TEXT PRIMARY KEY,
                customer TEXT,
                customer_name TEXT,
                transaction_date TEXT,
                docstatus INTEGER,
                modified TEXT,
                status TEXT
            )'
        );
        $conn->getPdo()->exec(
            'CREATE TABLE IF NOT EXISTS "tabSales Order Item" (
                name TEXT PRIMARY KEY,
                parent TEXT,
                item_code TEXT,
                qty REAL,
                stock_qty REAL,
                rate REAL,
                net_rate REAL,
                net_amount REAL
            )'
        );
    }

    private function seedSalesOrders(): void
    {
        DB::connection('mysql')->table('tabItem')->insert([
            'name' => self::ITEM_CODE,
            'item_code' => self::ITEM_CODE,
            'stock_uom' => 'Set(s)',
        ]);

        DB::connection('mysql')->table('tabSales Order')->insert([
            [
                'name' => 'SO-2026-00587',
                'customer' => 'CUST-A',
                'customer_name' => 'Bright Spaces Inc.',
                'transaction_date' => '2026-05-20',
                'docstatus' => 1,
                'modified' => '2026-05-20 10:00:00',
                'status' => 'To Deliver',
            ],
            [
                'name' => 'SO-2026-00100',
                'customer' => 'CUST-A',
                'customer_name' => 'Bright Spaces Inc.',
                'transaction_date' => '2026-01-10',
                'docstatus' => 1,
                'modified' => '2026-01-10 10:00:00',
                'status' => 'Completed',
            ],
            [
                'name' => 'SO-2026-00300',
                'customer' => 'CUST-B',
                'customer_name' => 'Acme Lighting',
                'transaction_date' => '2026-03-01',
                'docstatus' => 1,
                'modified' => '2026-03-01 10:00:00',
                'status' => 'Completed',
            ],
            [
                'name' => 'SO-DRAFT-001',
                'customer' => 'CUST-C',
                'customer_name' => 'Draft Customer',
                'transaction_date' => '2026-06-01',
                'docstatus' => 0,
                'modified' => '2026-06-01 10:00:00',
                'status' => 'Draft',
            ],
            [
                'name' => 'SO-OTHER-ITEM',
                'customer' => 'CUST-D',
                'customer_name' => 'Other Item Customer',
                'transaction_date' => '2026-05-15',
                'docstatus' => 1,
                'modified' => '2026-05-15 10:00:00',
                'status' => 'Completed',
            ],
        ]);

        DB::connection('mysql')->table('tabSales Order Item')->insert([
            [
                'name' => 'SOI-1',
                'parent' => 'SO-2026-00587',
                'item_code' => self::ITEM_CODE,
                'qty' => 10,
                'stock_qty' => 10,
                'rate' => 112,
                'net_rate' => 100,
                'net_amount' => 1000,
            ],
            [
                'name' => 'SOI-2',
                'parent' => 'SO-2026-00100',
                'item_code' => self::ITEM_CODE,
                'qty' => 5,
                'stock_qty' => 5,
                'rate' => 224,
                'net_rate' => 200,
                'net_amount' => 1000,
            ],
            [
                'name' => 'SOI-3',
                'parent' => 'SO-2026-00300',
                'item_code' => self::ITEM_CODE,
                'qty' => 2,
                'stock_qty' => 2,
                'rate' => 56,
                'net_rate' => 50,
                'net_amount' => 100,
            ],
            [
                'name' => 'SOI-DRAFT',
                'parent' => 'SO-DRAFT-001',
                'item_code' => self::ITEM_CODE,
                'qty' => 99,
                'stock_qty' => 99,
                'rate' => 100,
                'net_rate' => 100,
                'net_amount' => 9900,
            ],
            [
                'name' => 'SOI-OTHER',
                'parent' => 'SO-OTHER-ITEM',
                'item_code' => 'ITEM-OTHER',
                'qty' => 8,
                'stock_qty' => 8,
                'rate' => 100,
                'net_rate' => 100,
                'net_amount' => 800,
            ],
        ]);
    }

    private function createUser(string $id, string $group): User
    {
        DB::connection('mysql')->table('tabWarehouse Users')->insert([
            'name' => $id,
            'wh_user' => $id,
            'frappe_userid' => $id.'@test.com',
            'full_name' => $id,
            'email' => $id.'@test.com',
            'password' => bcrypt('password'),
            'user_group' => $group,
            'department' => 'Sales',
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        return User::find($id);
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get('/item_order_history/'.self::ITEM_CODE)
            ->assertRedirect(route('login'));
    }

    public function test_groups_unique_customers_and_vat_exclusive_totals(): void
    {
        $user = $this->createUser('staff-user', 'Sales User');

        $response = $this->actingAs($user)->getJson('/item_order_history/'.self::ITEM_CODE);

        $response->assertOk();
        $response->assertJsonPath('summary.unique_customers', 2);
        $response->assertJsonPath('summary.total_sales', 2100);
        $response->assertJsonPath('summary.total_qty', 17);
        $response->assertJsonPath('summary.average_order_value', 700);
        $response->assertJsonPath('summary.top_customer.customer_name', 'Bright Spaces Inc.');
        $response->assertJsonPath('summary.top_customer.total_sales', 2000);
        $this->assertEquals(95.24, $response->json('summary.top_customer.percentage'));
        $response->assertJsonPath('stock_uom', 'Set(s)');
        $response->assertJsonPath('erp_web_base_url', 'https://erp.test');
        $response->assertJsonPath('meta.total', 2);

        $rows = collect($response->json('rows'));
        $bright = $rows->firstWhere('customer', 'CUST-A');
        $this->assertNotNull($bright);
        $this->assertEquals(2000, $bright['total_sales']);
        $this->assertEquals(15, $bright['total_qty']);
        $this->assertEqualsWithDelta(2000 / 15, $bright['avg_selling_price'], 0.0001);
        $this->assertEquals(2, $bright['number_of_orders']);
        $this->assertEquals('SO-2026-00587', $bright['last_order_no']);
        $this->assertStringStartsWith('2026-05-20', (string) $bright['last_order_date']);
    }

    public function test_date_filter_limits_customers(): void
    {
        $user = $this->createUser('staff-user', 'Sales User');

        $response = $this->actingAs($user)->getJson('/item_order_history/'.self::ITEM_CODE.'?date_from=2026-04-01&date_to=2026-12-31');

        $response->assertOk();
        $response->assertJsonPath('summary.unique_customers', 1);
        $response->assertJsonPath('summary.total_sales', 1000);
        $response->assertJsonPath('meta.total', 1);
        $response->assertJsonPath('rows.0.customer', 'CUST-A');
        $response->assertJsonPath('rows.0.last_order_no', 'SO-2026-00587');
    }

    public function test_customer_filter_returns_only_that_customer(): void
    {
        $user = $this->createUser('staff-user', 'Sales User');

        $response = $this->actingAs($user)->getJson('/item_order_history/'.self::ITEM_CODE.'?customer=CUST-B');

        $response->assertOk();
        $response->assertJsonPath('summary.unique_customers', 1);
        $response->assertJsonPath('summary.total_sales', 100);
        $response->assertJsonPath('rows.0.customer', 'CUST-B');
        $response->assertJsonPath('rows.0.avg_selling_price', 50);
        $response->assertJsonPath('rows.0.number_of_orders', 1);
    }

    public function test_manager_and_non_manager_can_access(): void
    {
        $staff = $this->createUser('staff-user', 'Sales User');
        $this->actingAs($staff)->getJson('/item_order_history/'.self::ITEM_CODE)->assertOk();

        $manager = $this->createUser('manager-user', 'Manager');
        $this->actingAs($manager)->getJson('/item_order_history/'.self::ITEM_CODE)->assertOk();
    }

    public function test_empty_item_returns_empty_summary(): void
    {
        $user = $this->createUser('staff-user', 'Sales User');

        $response = $this->actingAs($user)->getJson('/item_order_history/ITEM-NONE');

        $response->assertOk();
        $response->assertJsonPath('summary.unique_customers', 0);
        $response->assertJsonPath('summary.total_sales', 0);
        $response->assertJsonPath('summary.top_customer', null);
        $response->assertJsonPath('meta.total', 0);
        $this->assertSame([], $response->json('rows'));
    }
}
