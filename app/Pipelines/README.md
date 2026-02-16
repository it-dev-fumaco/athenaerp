# Laravel Pipeline in AthenaERP

Pipelines let you pass a value (e.g. a request or DTO) through a series of **pipes**. Each pipe can validate, transform, or delegate before calling the next pipe. This keeps controllers thin and logic testable.

## Contract

All pipe classes should implement `App\Contracts\Pipeline\Pipe`:

```php
public function handle(mixed $passable, Closure $next): mixed
```

- **$passable** – The object being passed (often the HTTP request). You can attach data to it (e.g. `$passable->pipelineUser = $user`) for later pipes.
- **$next** – Call this with `$passable` to run the next pipe: `return $next($passable);`
- **Return** – Return the result of `$next($passable)` or a response (e.g. redirect) to short-circuit the pipeline.

## Example: Login

`LoginPipeline` runs the login flow:

1. **FindUserPipe** – Resolve user by email, attach to `$request->pipelineUser`.
2. **EnsureApiCredentialsPipe** – Ensure API credentials exist; throw if not.
3. **LoginUserPipe** – Log the user in and update `last_login`.

Usage in `LoginController`:

```php
return $this->loginPipeline->run($request);
```

## Example: Brochure upload

`BrochureUploadPipeline` runs the brochure Excel upload flow:

1. **ValidateBrochureFilePipe** – Validate file extension (.xlsx/.xls).
2. **ReadBrochureSpreadsheetPipe** – Parse spreadsheet via passable `readFileCallable`.
3. **EnsureBrochureDirectoryAndFilenamePipe** – Create project dir, compute `newFilename` and `transactionDate`.
4. **PersistBrochureLogPipe** – Insert `ProductBrochureLog` row.
5. **StoreBrochureFilePipe** – Move file to `storage/brochures/{project}/`.

Usage in `BrochureController::readExcelFile` (after handling readonly preview):

```php
$passable = (object) [
    'request' => $request,
    'file' => $attachedFile,
    'readFileCallable' => fn ($file) => $this->readFile($file),
];
return $this->brochureUploadPipeline->run($passable);
```

## Example: Email HR command

`EmailHRPipeline` runs the HR promodiser report email flow:

1. **LoadCutoffSettingsPipe** – Load cutoff dates from `tabConsignment Sales Report Deadline`.
2. **ComputeCutoffPeriodPipe** – If today is a cutoff day, compute `periodFrom` / `periodTo`; else set `shouldSendEmail = false`.
3. **LoadActivePromodisersPipe** – Load active promodisers (when sending).
4. **BuildHrMissingReportListPipe** – Build list of promodisers who haven’t submitted; set `emailData`.
5. **SendHrEmailsPipe** – Send mail to HR and consignment.

Usage in `EmailHR` command:

```php
$this->emailHRPipeline->run((object) []);
```

## Example: Update stock reservation command

`UpdateStockReservationPipeline` runs the stock reservation status updates:

1. **ExpireOldReservationsPipe** – Set status to `Expired` where `valid_until < now()`.
2. **MarkPartiallyIssuedReservationsPipe** – Set status to `Partially Issued` where `0 < consumed_qty < reserve_qty`.
3. **MarkIssuedReservationsPipe** – Set status to `Issued` where `consumed_qty >= reserve_qty`.

Usage in `UpdateStockReservation` command:

```php
$this->updateStockReservationPipeline->run((object) []);
```

## Example: Update stocks (pullout) command

`UpdateStocksPipeline` runs the consignment pullout sync:

1. **LoadSubmittedPulloutsPipe** – Load pending Consignment Stock Entries (Pull Out / Store Transfer) with submitted Stock Entry.
2. **LoadConsignmentStockEntryItemsPipe** – Load Consignment Stock Entry Detail rows for those entries.
3. **LoadBinsForPulloutPipe** – Load affected bins into `binArray` (warehouse × item_code).
4. **UpdateBinConsignedQtyPipe** – For each Pull Out item, decrement bin `consigned_qty` and persist.
5. **MarkConsignmentStockEntriesCompletedPipe** – Set Consignment Stock Entry status to `Completed`.

Usage in `UpdateStocks` command:

```php
$this->updateStocksPipeline->run((object) []);
```

## Example: View deliveries (picking list)

`ViewDeliveriesPipeline` runs the picking/delivery list flow:

1. **BuildPickingListPipe** – Build picking slip, stock entry, and product bundle queries; union and paginate.
2. **EnrichPickingListPipe** – Load supplier part nos, owner names, parent warehouses (via passable `getWarehouseParentsBulk` callable).
3. **FormatPickingResponsePipe** – Build `pickingList` and `pagination` on passable.

Usage in `DeliveryController::viewDeliveries` (when `$request->arr` is set):

```php
$passable = (object) [
    'allowedWarehouses' => $this->getAllowedWarehouseIds(),
    'search' => $request->search ?? '',
    'getWarehouseParentsBulk' => fn (array $warehouseNames) => $this->getWarehouseParentsBulk($warehouseNames),
];
return $this->viewDeliveriesPipeline->run($passable);
```

## Example: Consignment ledger

`ConsignmentLedgerPipeline` runs the consignment ledger data assembly:

1. **LoadBeginningInventoryLedgerPipe** – Load beginning inventory for branch; set `result`, `itemDescriptions`, `beginningInventoryStartDate`.
2. **LoadReceivedStocksLedgerPipe** – Load received stocks and merge into `result` / `itemDescriptions`.
3. **LoadStoreTransfersLedgerPipe** – Load store transfers and merge.
4. **LoadReturnsLedgerPipe** – Load returns and merge.

Usage in `ConsignmentController::consignmentLedger` (when `$request->ajax()`):

```php
$passable = (object) [
    'branchWarehouse' => $request->branch_warehouse,
    'itemCode' => $request->item_code,
];
return $this->consignmentLedgerPipeline->run($passable);
```

## Example: View material issue (MaterialTransferController)

`ViewMaterialIssuePipeline` runs the material issue list flow:

1. **LoadMaterialIssueEntriesPipe** – Load draft Stock Entry details (Material Issue) for allowed warehouses.
2. **EnrichMaterialIssuePipe** – Load SO customers, part nos, owner names, actual/available qty, parent warehouses (via passable callables).
3. **FormatMaterialIssueResponsePipe** – Build `records` for JSON response.

Usage in `MaterialTransferController::viewMaterialIssue` (when `$request->arr` is set):

```php
$passable = (object) [
    'allowedWarehouses' => $this->getAllowedWarehouseIds(),
    'getActualQtyBulk' => fn (array $pairs) => $this->getActualQtyBulk($pairs),
    'getAvailableQtyBulk' => fn (array $pairs) => $this->getAvailableQtyBulk($pairs),
    'getWarehouseParentsBulk' => fn (array $warehouses) => $this->getWarehouseParentsBulk($warehouses),
];
return $this->viewMaterialIssuePipeline->run($passable);
```

## Example: View material transfer for manufacture (MaterialTransferController)

`ViewMaterialTransferForManufacturePipeline` runs the material transfer for manufacture list flow:

1. **LoadMaterialTransferForManufactureEntriesPipe** – Load entries via passable `getMaterialTransferForManufactureEntries` callable.
2. **BuildMaterialTransferForManufactureLookupPipe** – Build lookup data via passable `buildMaterialTransferLookupData` callable.
3. **FormatMaterialTransferForManufactureRecordsPipe** – Build records via passable `buildMaterialTransferRecordsList` callable.

Usage in `MaterialTransferController::viewMaterialTransferForManufacture` (when `$request->arr` is set):

```php
$passable = (object) [
    'allowedWarehouses' => $this->getAllowedWarehouseIds(),
    'getMaterialTransferForManufactureEntries' => fn ($allowedWarehouses) => $this->getMaterialTransferForManufactureEntries($allowedWarehouses),
    'buildMaterialTransferLookupData' => fn ($entries) => $this->buildMaterialTransferLookupData($entries),
    'buildMaterialTransferRecordsList' => fn ($entries, $lookupData) => $this->buildMaterialTransferRecordsList($entries, $lookupData),
];
return $this->viewMaterialTransferForManufacturePipeline->run($passable);
```

## Example: View material transfer (MaterialTransferController)

`ViewMaterialTransferPipeline` runs the material transfer list flow (union of source/target warehouse entries):

1. **LoadMaterialTransferEntriesPipe** – Load entries via passable `getMaterialTransferEntries` callable.
2. **BuildMaterialTransferViewLookupPipe** – Build lookup data via passable `buildMaterialTransferViewLookupData` callable.
3. **FormatMaterialTransferViewRecordsPipe** – Build records via passable `buildMaterialTransferViewRecordsList` callable.

Usage in `MaterialTransferController::viewMaterialTransfer` (when `$request->arr` is set):

```php
$passable = (object) [
    'allowedWarehouses' => $this->getAllowedWarehouseIds(),
    'getMaterialTransferEntries' => fn (array $allowedWarehouses) => $this->getMaterialTransferEntries($allowedWarehouses),
    'buildMaterialTransferViewLookupData' => fn ($entries) => $this->buildMaterialTransferViewLookupData($entries),
    'buildMaterialTransferViewRecordsList' => fn ($entries, $lookupData) => $this->buildMaterialTransferViewRecordsList($entries, $lookupData),
];
return $this->viewMaterialTransferPipeline->run($passable);
```

## Example: Main index (MainController)

`IndexPipeline` runs the main dashboard/index flow:

1. **UpdateReservationStatusPipe** – Call passable `updateReservationStatus` callable (e.g. from GeneralTrait).
2. **ResolveIndexResponsePipe** – Resolve response by user group: User → redirect to search; Promodiser → load promodiser dashboard data and view; Consignment Supervisor → call passable `getConsignmentDashboardView`; default → view index.

Usage in `MainController::index`:

```php
$passable = (object) [
    'updateReservationStatus' => fn () => $this->updateReservationStatus(),
    'getConsignmentDashboardView' => fn () => $this->viewConsignmentDashboard(),
];
return $indexPipeline->run($passable);
```

## Adding a new pipeline

1. **Create pipes** in `app/Pipelines/Pipes/`, each implementing `App\Contracts\Pipeline\Pipe`.
2. **Create a pipeline class** (e.g. `app/Pipelines/SomeFeaturePipeline.php`) that uses `Illuminate\Pipeline\Pipeline`:

   ```php
   use Illuminate\Pipeline\Pipeline;

   return $this->pipeline
       ->send($request)
       ->through([PipeOne::class, PipeTwo::class])
       ->then(fn ($passable) => $this->finalAction($passable));
   ```

3. **Inject the pipeline** in your controller and call `->run($request)` (or equivalent).

## When to use

- Multi-step flows: validate → load → transform → persist → respond.
- Shared steps across controllers (e.g. “resolve user + check warehouse access”).
- Keeping controllers thin while keeping each step in a single, testable class.

## When not to use

- Single, trivial actions (a single DB update or redirect).
- Flows that are used only once and have no reusable steps; a service or trait may be simpler.
