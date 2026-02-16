# AthenaERP Upgrade Guide: Laravel 10 & PHP 8.3

This document describes the upgrade process from the previous Laravel version to **Laravel 10** and **PHP 8.3**.

---

## 1. Overview

| Component | Before | After |
|-----------|--------|-------|
| PHP | 8.0 / 8.1 | **8.1–8.3** (8.3 recommended) |
| Laravel | 8.x / 9.x | **10.x** |
| Composer | varies | 2.x |

---

## Phase 1: Code Compliance (Complete)

Phase 1 applies the code changes in **Section 5** across the application so that controllers, commands, and app code follow Laravel 10 and project standards.

| Item | Section | Status |
|------|---------|--------|
| Facade imports (`use Illuminate\Support\Facades\*`, `Carbon\Carbon`) | 5.1 | Done |
| Date/time: `now()` instead of `Carbon::now()` | 5.2 | Done |
| Array/object access: `Arr::get()`, `data_get()` | 5.3 | Done |
| Config: `config()` in app code, `env()` only in config files | 5.4 | Done |
| Nullable access: `optional()`, nullsafe `?->` | 5.5 | Done |
| Variable naming: camelCase (e.g. `$searchTerms`, `$itemDetails`) | 5.6 | Done |
| Console commands: `return self::SUCCESS` / `self::FAILURE` | 5.7 | Done |
| PhpSpreadsheet: `getCell([$col,$row])`, `setCellValue([$col,$row], $value)` | 5.8 | Done |
| API responses: `ApiResponse::success()` / `ApiResponse::failure()` | 5.9 | Done |
| Logic in models: scopes, accessors (e.g. ProductBrochureLog, User) | 5.10 | Done |

**Phase 1 is complete.** Continue with Phase 2 (e.g. testing, deployment) or further refactors as needed.

---

## 2. Prerequisites

- **PHP 8.1–8.3** (8.3 recommended)
- **Composer 2.x**
- **Node.js 18+** and npm (for frontend)

---

## 3. Environment Configuration

### 3.1 New / Renamed Environment Keys

Laravel 10 uses updated environment key names. The config files support both old and new keys for backward compatibility:

| Old Key | New Key | Notes |
|---------|---------|-------|
| `BROADCAST_DRIVER` | `BROADCAST_CONNECTION` | Config falls back to old key |
| `CACHE_DRIVER` | `CACHE_STORE` | Config falls back to old key |
| `FILESYSTEM_DRIVER` | `FILESYSTEM_DISK` | Config falls back to old key |

**Action:** Update `.env` to use the new keys. Existing keys will still work until removed.

### 3.2 MES Database Connection

A second database connection (`mysql_mes`) was added for the MES database. Add these to `.env`:

```env
# MES Database (mysql_mes connection)
DB_HOST_1=127.0.0.1
DB_PORT_1=3306
DB_DATABASE_1=
DB_USERNAME_1=
DB_PASSWORD_1=
```

---

## 4. Composer Dependencies

### 4.1 Updated Package Versions

- `laravel/framework`: `^10.0`
- `php`: `^8.1|^8.2|^8.3`
- `phpunit/phpunit`: `^10.5` (for PHP 8.1+)
- Other packages updated for compatibility

### 4.2 Upgrade Steps

```bash
composer update
```

---

## 5. Code Changes Applied

### 5.1 Facade Imports

**Before:**
```php
use DB;
use Mail;
use Carbon;
```

**After:**
```php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
```

### 5.2 Date / Time Helpers

**Before:**
```php
Carbon::now()->format('Y-m-d');
Carbon::now()->startOfMonth();
```

**After:**
```php
now()->format('Y-m-d');
now()->startOfMonth();
```

### 5.3 Array & Object Access

**Before:**
```php
if (array_key_exists($key, $arr)) {
    $value = $arr[$key];
}
$value = isset($arr[$key]) ? $arr[$key] : $default;
```

**After:**
```php
$value = Arr::get($arr, $key, $default);
$value = data_get($arr, $key, $default);  // works for objects too
```

### 5.4 Configuration Access

**Before:**
```php
$url = env('ERP_API_BASE_URL');
```

**After:**
```php
$url = config('services.erp.url');  // add to config/services.php
```

Use `config()` in application code. Use `env()` only in config files.

### 5.5 Nullable Object Access

**Before:**
```php
$name = $user ? $user->profile->name : null;
```

**After:**
```php
$name = optional($user)->profile?->name;
```

### 5.6 Variable Naming

Use **camelCase** for variables:

```php
// Before
$item_details = Item::find($id);
$search_str = explode(' ', $request->search);

// After
$itemDetails = Item::find($id);
$searchTerms = explode(' ', $request->search);
```

### 5.7 Console Command Return Values

**Before:**
```php
return 0;
```

**After:**
```php
return self::SUCCESS;
return self::FAILURE;  // for errors
```

### 5.8 PhpSpreadsheet API Changes

`getCellByColumnAndRow()` and `setCellValueByColumnAndRow()` are deprecated. Use coordinate arrays:

**Before:**
```php
$value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
$sheet->setCellValueByColumnAndRow($column, $row, $value);
```

**After:**
```php
$value = $sheet->getCell([$col, $row])->getValue();
$sheet->setCellValue([$column, $row], $value);
```

### 5.9 Standardized API Responses

`App\Http\Helpers\ApiResponse` replaces ad-hoc JSON responses:

**Before:**
```php
return response()->json(['status' => 0, 'message' => 'Error message']);
return response()->json(['status' => 1, 'message' => 'Success', 'data' => $data]);
```

**After:**
```php
return ApiResponse::failure('Error message');
return ApiResponse::success('Success', $data);
```

Methods available:
- `ApiResponse::success($message, $data)` – `status: 1`
- `ApiResponse::failure($message)` – `status: 0`
- `ApiResponse::successLegacy(...)` / `ApiResponse::failureLegacy(...)` – `success: 1/0` for legacy clients

### 5.10 Logic Moved to Models

Business logic and repeated queries were moved into models using scopes and methods:

**Example – ProductBrochureLog:**
```php
// Controller: ProductBrochureLog::recentUploads($request->search)
// Model: scopeRecentUploads(), getHumanDurationAttribute()
```

**Example – User:**
```php
// Controller: $warehouses = WarehouseAccess::where('parent', $user)->pluck('warehouse');
// Model: Auth::user()->allowedWarehouseIds();
```

---

## 6. Testing After Upgrade

1. **Run tests:**
   ```bash
   php artisan test
   ```

2. **Smoke tests:**
   - Login
   - Critical flows (search, consignment, brochure, etc.)
   - Scheduled commands (if applicable)

3. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## 7. Rollback (if needed)

1. Restore `.env` from backup.
2. Restore `composer.json` and `composer.lock`.
3. Run `composer install`.
4. Restore application code from version control.

---

## 8. References

- [Laravel 10 Upgrade Guide](https://laravel.com/docs/10.x/upgrade)
- [PHP 8.3 Migration Guide](https://www.php.net/manual/en/migration83.php)
- [PhpSpreadsheet Changelog](https://github.com/PHPOffice/PhpSpreadsheet/blob/master/CHANGELOG.md)

---

## 9. Project Coding Standards (Post-Upgrade)

The project uses Cursor rules in `.cursor/rules/`:

- **laravel-helpers.mdc** – `Arr::get()`, `data_get()`, `config()`, `now()`, `optional()`, `Str::`
- **variable-naming.mdc** – camelCase, descriptive names
- **models-providers.mdc** – Scopes and business logic in models, thin controllers

Follow these when adding or modifying code.
