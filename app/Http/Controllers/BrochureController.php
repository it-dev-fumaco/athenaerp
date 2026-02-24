<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Helpers\SafePath;
use App\Http\Requests\AddToBrochureListRequest;
use App\Http\Requests\ReadBrochureExcelRequest;
use App\Http\Requests\UpdateBrochureAttributesRequest;
use App\Http\Requests\UploadBrochureImageRequest;
use App\Http\Requests\UploadStandardBrochureImageRequest;
use App\Models\Item;
use App\Models\ItemBrochureImage;
use App\Models\ItemImages;
use App\Models\ItemVariantAttribute;
use App\Models\ProductBrochureLog;
use App\Pipelines\BrochureUploadPipeline;
use App\Services\BrochureAttributeService;
use App\Services\BrochureExcelService;
use App\Services\BrochureImageService;
use App\Services\BrochurePdfService;
use App\Traits\GeneralTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class BrochureController extends Controller
{
    use GeneralTrait;

    public function __construct(
        protected BrochureUploadPipeline $brochureUploadPipeline,
        protected BrochureImageService $brochureImageService,
        protected BrochurePdfService $brochurePdfService,
        protected BrochureAttributeService $brochureAttributeService,
        protected BrochureExcelService $brochureExcelService
    ) {}

    public function viewForm(Request $request)
    {
        if ($request->ajax()) {
            $recents = ProductBrochureLog::recentUploads($request->search);
            $recentUploads = $recents->map(fn ($row) => [
                'project' => $row->project,
                'filename' => $row->filename,
                'created_by' => $row->created_by,
                'duration' => $row->human_duration,
            ])->all();

            return view('brochure.history', compact('recentUploads'));
        }

        return view('brochure.form');
    }

    public function readExcelFile(ReadBrochureExcelRequest $request)
    {
        DB::beginTransaction();
        try {
            $attachedFile = $request->file('selected-file');

            if ($request->is_readonly) {
                $fileContents = $this->brochureExcelService->readFile($attachedFile);
                $content = $fileContents['content'];
                $project = isset($fileContents['project']) && $fileContents['project']
                    ? trim(str_replace('/', '-', (string) $fileContents['project']))
                    : '-';
                $customer = $fileContents['customer'];
                $headers = $fileContents['headers'];

                DB::rollBack();

                return view('brochure.modal_product_list', compact('content', 'project', 'customer', 'headers'));
            }

            $passable = (object) [
                'request' => $request,
                'file' => $attachedFile,
                'readFileCallable' => fn ($file) => $this->brochureExcelService->readFile($file),
            ];

            $response = $this->brochureUploadPipeline->run($passable);
            DB::commit();

            return $response;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Brochure upload failed: '.$e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $message = config('app.debug')
                ? $e->getMessage().' (in '.basename($e->getFile()).':'.$e->getLine().')'
                : 'Something went wrong. Please try again.';

            return ApiResponse::failure($message);
        }
    }

    public function previewBrochure(Request $request, $project, $filename)
    {
        try {
            ini_set('max_execution_time', '300');
            $projectParam = trim((string) $project);
            $filename = trim((string) $filename);
            $brochuresRelativePath = 'brochures/'.strtoupper($projectParam).'/'.$filename;
            if (SafePath::pathContainsTraversal($projectParam) || SafePath::pathContainsTraversal($filename) || ! SafePath::pathUnderPrefix($brochuresRelativePath, 'brochures')) {
                return redirect('brochure')->with('error', 'Invalid path.');
            }

            $upcloudDisk = Storage::disk('upcloud');
            $storageExists = $upcloudDisk->exists($brochuresRelativePath);

            if (! $storageExists) {
                return redirect('brochure')->with('error', 'File '.$filename.' does not exist.');
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'brochure_');
            file_put_contents($tempPath, $upcloudDisk->get($brochuresRelativePath));
            try {
                $fileContents = $this->brochureExcelService->readFile($tempPath);
            } finally {
                if (is_file($tempPath)) {
                    @unlink($tempPath);
                }
            }
            $content = collect($fileContents['content'])->map(function ($row) {
                if ($row['id'] && collect($row['attributes'])->pluck('attribute_value')->filter()->values()->all()) {
                    return $row;
                }

                return null;
            })->filter()->values()->all();
            $projectFromFile = trim((string) $fileContents['project']);
            $project = $projectFromFile ?: $projectParam;
            $tableOfContents = $fileContents['table_of_contents'];

            if (isset($request->pdf) && $request->pdf) {
                $storage = Storage::disk('upcloud')->files('brochures/'.strtoupper($project));
                $series = null;
                if ($storage) {
                    $series = count($storage) > 1 ? count($storage) : 1;
                    $series = '-'.(string) $series;
                }
                $newFilename = Str::slug($project, '-').'-'.now()->format('Y-m-d').$series;
                $isStandard = false;
                $content = $this->brochurePdfService->resolveBrochureImagePathsForPdf($content, $project, false);
                $pdf = Pdf::loadView('brochure.pdf', compact('content', 'project', 'filename', 'isStandard'));

                return $pdf->stream($newFilename.'.pdf');
            }

            return view('brochure.print_preview', compact('content', 'tableOfContents', 'project', 'filename'));
        } catch (\Throwable $th) {
            return redirect('brochure')->with('error', 'An error occured. Please try again.');
        }
    }

    public function uploadImage(UploadBrochureImageRequest $request)
    {
        DB::beginTransaction();
        try {
            $file = $request->file('selected-file');
            $folder = $request->project;
            $dir = $request->filename;

            $filename = $this->brochureImageService->storeSpreadsheetImage($file, $folder);

            $brochuresRelativePath = 'brochures/'.strtoupper($folder).'/'.$dir;
            if (SafePath::pathContainsTraversal($folder) || SafePath::pathContainsTraversal($dir) || ! SafePath::pathUnderPrefix($brochuresRelativePath, 'brochures')) {
                DB::rollBack();

                return ApiResponse::failure('Invalid path.');
            }
            $upcloudDisk = Storage::disk('upcloud');
            if (! $upcloudDisk->exists($brochuresRelativePath)) {
                DB::rollBack();

                return ApiResponse::failure('Brochure file not found. Save the brochure first.');
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'brochure_');
            try {
                file_put_contents($tempPath, $upcloudDisk->get($brochuresRelativePath));

                $this->brochureExcelService->ensureImageColumnsExist($tempPath);

                $column = $this->brochureExcelService->findColumnIndexByHeader($tempPath, $request->column);
                if ($column === null) {
                    DB::rollBack();

                    return ApiResponse::failure(
                        'Column "'.$request->column.'" not found. Add "Image 1", "Image 2", "Image 3" in row 4 of the Excel if missing.'
                    );
                }

                $this->brochureExcelService->setCellValueAndSave($tempPath, $column, (int) $request->row, $filename);

                $upcloudDisk->put($brochuresRelativePath, file_get_contents($tempPath));
            } finally {
                if (is_file($tempPath)) {
                    @unlink($tempPath);
                }
            }

            $transactionDate = now()->toDateTimeString();
            ProductBrochureLog::insert([
                'name' => uniqid(),
                'creation' => $transactionDate,
                'modified' => $transactionDate,
                'modified_by' => $request->ip(),
                'owner' => $request->ip(),
                'project' => $folder,
                'filename' => $filename,
                'created_by' => $request->ip(),
                'transaction_date' => $transactionDate,
                'remarks' => 'For '.$dir,
                'transaction_type' => 'Upload Image',
            ]);

            DB::commit();

            return ApiResponse::success('Image uploaded.', [
                'src' => $filename,
                'item_image_id' => $request->item_image_id,
            ]);
        } catch (RuntimeException $e) {
            DB::rollBack();
            Log::error('Brochure image upload failed: '.$e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return ApiResponse::failure($e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Brochure image upload failed: '.$e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $message = config('app.debug')
                ? $e->getMessage().' (in '.basename($e->getFile()).':'.$e->getLine().')'
                : 'Something went wrong. Please try again.';

            return ApiResponse::failure($message);
        }
    }

    public function downloadBrochure($project, $file)
    {
        try {
            $project = trim((string) $project);
            $file = trim((string) $file);
            if (SafePath::pathContainsTraversal($project) || SafePath::pathContainsTraversal($file) || ! SafePath::pathUnderPrefix('brochures/'.strtoupper($project).'/'.$file, 'brochures')) {
                return ApiResponse::failureLegacy('File not found');
            }

            if (! Storage::disk('upcloud')->exists('brochures/'.strtoupper($project).'/'.$file)) {
                return ApiResponse::failureLegacy('File not found');
            }

            $storage = Storage::disk('upcloud')->files('brochures/'.strtoupper($project));
            $series = null;
            if ($storage) {
                $series = count($storage) > 1 ? count($storage) : 1;
                $series = '-'.(string) $series;
            }
            $newFilename = Str::slug($project, '-').'-'.now()->format('Y-m-d').$series;
            $ext = explode('.', $file);
            $ext = $ext[1] ?? 'xlsx';
            $newName = $newFilename.'.'.$ext;
            $origPath = strtoupper($project).'/'.$file;

            return response()->json([
                'success' => 1,
                'new_name' => $newName,
                'orig_path' => $origPath,
            ]);
        } catch (Exception $e) {
            return ApiResponse::failureLegacy('Something went wrong. Please try again.');
        }
    }

    public function removeImage(Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->id) {
                ItemBrochureImage::where('name', $request->id)->delete();
                DB::commit();

                return ApiResponse::success('Image removed.');
            }

            $folder = $request->project ? trim((string) $request->project) : null;
            $dir = $request->filename ? trim((string) $request->filename) : null;
            $column = $request->column ? trim((string) $request->column) : null;
            $row = $request->row !== null && $request->row !== '' ? (int) $request->row : null;

            if (! $folder || ! $dir || ! $column || $row === null || $row < 1) {
                return ApiResponse::failure('Project, filename, column and row are required to remove an image.');
            }

            $brochuresRelativePath = 'brochures/'.strtoupper($folder).'/'.$dir;
            if (SafePath::pathContainsTraversal($folder) || SafePath::pathContainsTraversal($dir) || ! SafePath::pathUnderPrefix($brochuresRelativePath, 'brochures')) {
                return ApiResponse::failure('Invalid path.');
            }

            $upcloudDisk = Storage::disk('upcloud');
            if (! $upcloudDisk->exists($brochuresRelativePath)) {
                return ApiResponse::failure('Brochure file not found.');
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'brochure_');
            try {
                file_put_contents($tempPath, $upcloudDisk->get($brochuresRelativePath));
                $this->brochureExcelService->ensureImageColumnsExist($tempPath);
                $columnIndex = $this->brochureExcelService->findColumnIndexByHeader($tempPath, $column);
                if ($columnIndex === null) {
                    return ApiResponse::failure('Column "'.$column.'" not found in the spreadsheet.');
                }
                $this->brochureExcelService->setCellValueAndSave($tempPath, $columnIndex, $row, null);
                $upcloudDisk->put($brochuresRelativePath, file_get_contents($tempPath));
            } finally {
                if (is_file($tempPath)) {
                    @unlink($tempPath);
                }
            }

            DB::commit();

            return ApiResponse::success('Image removed.', [
                'item_image_id' => $request->item_image_id,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::warning('Brochure remove image failed: '.$e->getMessage());

            return ApiResponse::failure('Something went wrong. Please try again.');
        }
    }

    public function countBrochures()
    {
        $list = $itemCodes = [];
        if (session()->has('brochure_list')) {
            $list = session()->get('brochure_list.items');
            $list = isset($list) ? collect($list)->sortBy('idx')->toArray() : [];
            $itemCodes = collect($list)->pluck('item_code')->unique()->values()->all();
        }

        $itemArr = collect();
        if (! empty($itemCodes)) {
            $itemArr = Item::query()
                ->whereIn('name', $itemCodes)
                ->select('name', 'item_name')
                ->get()
                ->groupBy('name');
        }

        return view('brochure.brochure_floater', compact('itemArr', 'list'));
    }

    public function addToBrochureList(AddToBrochureListRequest $request)
    {
        DB::beginTransaction();
        try {
            $save = isset($request->save) ? 1 : 0;
            $itemCodes = $request->item_codes ?? [];
            $fittingType = $request->fitting_type ?? [];
            $location = $request->location ?? [];
            $idArr = $request->id_arr ?? [];
            $itemBrochureDescription = $request->description ?? [];
            $itemBrochureName = $request->item_name ?? [];
            $project = $request->project ?? null;
            $customer = $request->customer ?? null;

            $counter = session()->get('brochure_list.items');
            $counter = isset($counter) ? count($counter) + 1 : 1;

            session()->put('brochure_list.project', $project);
            session()->put('brochure_list.customer', $customer);

            foreach ($itemCodes as $idx => $itemCode) {
                $id = $save
                    ? ($idArr[$idx] ?? $itemCode.'-0')
                    : $itemCode.'-'.$counter;
                $details = [
                    'item_code' => $itemCode,
                    'fitting_type' => $fittingType[$id] ?? null,
                    'location' => $location[$id] ?? null,
                    'idx' => $idx,
                ];
                session()->put('brochure_list.items.'.$id, $details);
            }

            $this->brochureAttributeService->syncItemBrochureFields(
                $itemCodes,
                $itemBrochureDescription,
                $itemBrochureName
            );

            DB::commit();

            $showNotif = isset($request->generate_page) ? 0 : 1;

            return ApiResponse::successWith('Item added to list.', ['show_notif' => $showNotif]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return ApiResponse::failure('An error occured. Please try again.');
        }
    }

    public function removeFromBrochureList($key)
    {
        session()->forget('brochure_list.items.'.$key);
    }

    public function generateMultipleBrochures(Request $request)
    {
        DB::beginTransaction();
        try {
            $session = session()->get('brochure_list');
            $brochureList = isset($session['items']) ? collect($session['items'])->sortBy('idx')->toArray() : [];
            $project = $session['project'] ?? null;
            $customer = $session['customer'] ?? null;
            $itemCodes = collect($brochureList)->pluck('item_code');

            $itemDetailsQry = Item::whereIn('name', $itemCodes)->get();
            $itemDetailsGroup = $itemDetailsQry->groupBy('name');

            $preview = isset($request->preview) && $request->preview ? 1 : 0;
            $pdf = isset($request->pdf) && $request->pdf ? 1 : 0;

            if ($pdf) {
                set_time_limit(300);
                ini_set('max_execution_time', 3600);
                ini_set('memory_limit', '4096M');
            }

            $attributesQry = ItemVariantAttribute::query()
                ->join('tabItem Attribute as attr', 'attr.name', 'tabItem Variant Attribute.attribute')
                ->whereIn('tabItem Variant Attribute.parent', $itemCodes)
                ->when($preview || $pdf, fn ($query) => $query->where('tabItem Variant Attribute.hide_in_brochure', 0))
                ->select('tabItem Variant Attribute.parent', 'tabItem Variant Attribute.attribute', 'tabItem Variant Attribute.attribute_value', 'attr.name', 'attr.attr_name', 'tabItem Variant Attribute.brochure_idx', 'tabItem Variant Attribute.hide_in_brochure')
                ->orderByRaw('LENGTH(`tabItem Variant Attribute`.`brochure_idx`) ASC')
                ->orderBy('tabItem Variant Attribute.brochure_idx', 'ASC')
                ->orderBy('tabItem Variant Attribute.idx')
                ->get();
            $attributeGroup = $attributesQry->groupBy('parent');

            $currentItemImagesQry = ItemImages::whereIn('parent', $itemCodes)->get();
            $currentItemImagesGroup = $currentItemImagesQry->groupBy('parent');

            $brochureImagesQry = ItemBrochureImage::whereIn('parent', $itemCodes)
                ->select('parent', 'image_filename', 'idx', 'image_path', 'name')
                ->orderByRaw('LENGTH(idx) ASC')
                ->orderBy('idx', 'ASC')
                ->get();
            $brochureImagesGroup = $brochureImagesQry->groupBy('parent')->toArray();

            $content = [];
            $no = 1;
            foreach ($brochureList as $key => $details) {
                if (in_array($key, ['project', 'customer'], true)) {
                    continue;
                }

                $itemCode = $details['item_code'];
                $itemDetails = $itemDetailsGroup[$itemCode][0] ?? null;
                if (is_array($itemDetails)) {
                    $itemDetails = (object) $itemDetails;
                }
                if (! $itemDetails) {
                    continue;
                }

                $attributes = $attributeGroup[$itemCode] ?? [];
                $currentItemImages = $currentItemImagesGroup[$itemCode] ?? [];
                $brochureImages = $brochureImagesGroup[$itemCode] ?? [];

                $itemName = $itemDetails->item_brochure_name ?: $itemDetails->item_name;
                $itemDescription = $itemDetails->item_brochure_description ?: $itemDetails->description;

                $attrib = [];
                $attributesArr = [];
                foreach ($attributes as $att) {
                    if (! $att || ! is_object($att)) {
                        continue;
                    }
                    $attrib[$att->attribute] = $att->attribute_value;
                    $attributesArr[] = [
                        'attribute_name' => $att->attr_name ?? $att->attribute,
                        'attribute_value' => $att->attribute_value,
                    ];
                }

                $currentImages = [];
                foreach ($currentItemImages as $e) {
                    if (! $e || ! is_object($e)) {
                        continue;
                    }
                    $filename = $e->image_path;
                    $currentImages[] = [
                        'filename' => $filename,
                        'filepath' => 'storage/img/'.$filename,
                    ];
                }

                $images = [];
                for ($i = 0; $i < 3; $i++) {
                    $row = $i + 1;
                    $img = $brochureImages[$i] ?? null;
                    $images['image'.$row] = [
                        'id' => $img?->name ?? null,
                        'filepath' => ($img && isset($img->image_path, $img->image_filename)) ? $img->image_path.$img->image_filename : null,
                    ];
                }

                $content[] = [
                    'item_code' => $itemCode,
                    'id' => Str::slug($itemName, '-'),
                    'row' => $i + 1,
                    'project' => $project,
                    'item_name' => $itemName,
                    'reference' => $details['fitting_type'],
                    'description' => $itemDescription,
                    'location' => $details['location'],
                    'current_images' => $currentImages,
                    'images' => $images,
                    'attributes' => $attributesArr,
                    'attrib' => $attrib,
                    'remarks' => $itemDetails->item_brochure_remarks,
                    'key' => $key,
                    'idx' => $no++,
                ];
            }

            $fumacoLogo = Storage::disk('upcloud')->url('fumaco_logo.png');

            if ($preview) {
                return view('brochure.preview_loop', compact('content', 'project', 'customer', 'fumacoLogo'));
            }

            if ($pdf) {
                $isStandard = true;
                $filename = Str::slug($project, '-');
                $newFilename = Str::slug($project, '-').'-'.now()->format('Y-m-d');
                $remarks = '';
                $pdfDoc = Pdf::loadView('brochure.pdf', compact('content', 'project', 'filename', 'isStandard', 'remarks'));

                return $pdfDoc->stream($newFilename.'.pdf');
            }

            return view('brochure.multiple_brochure', compact('content', 'project', 'customer'));
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'An error occured. Please try again.');
        }
    }

    public function generateBrochure(Request $request)
    {
        DB::beginTransaction();
        try {
            ini_set('max_execution_time', '300');
            $data = $request->all();

            $attributes = ItemVariantAttribute::query()
                ->join('tabItem Attribute as attr', 'attr.name', 'tabItem Variant Attribute.attribute')
                ->where('tabItem Variant Attribute.parent', $data['item_code'])
                ->where('tabItem Variant Attribute.hide_in_brochure', 0)
                ->select('tabItem Variant Attribute.attribute', 'tabItem Variant Attribute.attribute_value', 'attr.name', 'attr.attr_name')
                ->orderByRaw('LENGTH(`tabItem Variant Attribute`.`brochure_idx`) ASC')
                ->orderBy('tabItem Variant Attribute.brochure_idx', 'ASC')
                ->orderBy('tabItem Variant Attribute.idx')
                ->get();

            $remarks = Item::where('name', $data['item_code'])->value('item_brochure_remarks');

            $upcloudDisk = Storage::disk('upcloud');
            $currentItemImages = ItemImages::where('parent', $data['item_code'])->get();
            $currentImages = [];
            foreach ($currentItemImages as $e) {
                $filename = $e->image_path;
                if ($filename && ! $upcloudDisk->exists('item-images/'.$filename)) {
                    $filename = explode('.', $filename)[0].'.webp';
                }
                $currentImages[] = [
                    'filename' => $filename,
                    'filepath' => $filename ? 'item-images/'.$filename : null,
                ];
            }

            $brochureImages = ItemBrochureImage::where('parent', $data['item_code'])
                ->select('image_filename', 'idx', 'image_path', 'name')
                ->orderByRaw('LENGTH(idx) ASC')
                ->orderBy('idx', 'ASC')
                ->get();

            $images = [];
            for ($i = 0; $i < 3; $i++) {
                $row = $i + 1;
                $storageKey = null;
                if (isset($brochureImages[$i])) {
                    $pathPrefix = $brochureImages[$i]->image_path;
                    if ($pathPrefix === null || trim((string) $pathPrefix) === '' || ! Str::startsWith(trim((string) $pathPrefix), 'item-brochures')) {
                        $pathPrefix = 'item-brochures/';
                    } else {
                        $pathPrefix = rtrim($pathPrefix, '/').'/';
                    }
                    $storageKey = $pathPrefix.$brochureImages[$i]->image_filename;
                }
                $images['image'.$row] = [
                    'id' => $brochureImages[$i]->name ?? null,
                    'filepath' => $storageKey,
                ];
            }

            $fumacoLogo = Storage::disk('upcloud')->url('fumaco_logo.png');

            if (isset($request->get_images) && $request->get_images) {
                return view('brochure.brochure_images', compact('images', 'currentImages'));
            }

            if (isset($request->pdf) && $request->pdf) {
                $newFilename = Str::slug($request->item_name, '-').'-'.now()->format('Y-m-d');
                $project = $request->project;
                $filename = $request->filename;

                $attrib = [];
                $attributesArr = [];
                foreach ($attributes as $att) {
                    $attrib[$att->attribute] = $att->attribute_value;
                    $attributesArr[] = [
                        'attribute_name' => $att->attr_name ?: $att->attribute,
                        'attribute_value' => $att->attribute_value,
                    ];
                }

                $this->brochureAttributeService->updateItemBrochureFields(
                    $request->item_code,
                    $request->item_name,
                    $request->description
                );

                $content = [];
                $content[] = [
                    'id' => Str::slug($request->item_name, '-'),
                    'row' => 1,
                    'project' => $request->project,
                    'item_name' => $request->item_name,
                    'images' => $images,
                    'reference' => $request->reference,
                    'description' => $request->description,
                    'location' => $request->location,
                    'attributes' => $attributesArr,
                    'attrib' => $attrib,
                    'remarks' => $remarks,
                ];

                $content = $this->brochurePdfService->resolveStandardBrochureImageDataUris($content, $images);

                $isStandard = true;
                DB::commit();

                $pdfDoc = Pdf::loadView('brochure.pdf', compact('content', 'project', 'filename', 'isStandard', 'remarks', 'fumacoLogo'));

                return $pdfDoc->stream($newFilename.'.pdf');
            }

            $imgCheck = collect($currentImages)->map(function ($imageRow) use ($upcloudDisk) {
                $key = $imageRow['filepath'] ?? null;

                return $key && $upcloudDisk->exists($key) ? 1 : 0;
            })->max();

            return view('brochure.preview_standard_brochure', compact('data', 'attributes', 'images', 'currentImages', 'imgCheck', 'remarks', 'fumacoLogo'));
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function uploadImageForStandard(UploadStandardBrochureImageRequest $request)
    {
        DB::beginTransaction();
        try {
            Log::info('uploadImageForStandard started', [
                'project' => $request->project,
                'item_code' => $request->item_code,
                'image_idx' => $request->image_idx,
                'existing' => (bool) $request->existing,
                'has_file' => $request->hasFile('selected-file'),
                'original_name' => $request->hasFile('selected-file') ? $request->file('selected-file')->getClientOriginalName() : null,
            ]);

            $project = $request->project;
            $itemCode = $request->item_code;
            $transactionDate = now()->toDateTimeString();
            $storedFilename = null;
            $imagePath = null;
            $filename = null;

            if ($request->existing) {
                $filename = $request->selected_image;
                $storedFilename = $this->brochureImageService->getExistingImageStoredFilename($filename, 'item-images/');
                $imagePath = 'item-images/';
            }

            if ($request->hasFile('selected-file')) {
                $file = $request->file('selected-file');
                if (! $this->brochureImageService->validateImageExtension($file)) {
                    DB::rollBack();

                    return ApiResponse::failure('Sorry, only .jpeg, .jpg and .png files are allowed.');
                }

                $filename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $filename = str_replace(' ', '-', $filename);

                try {
                    $result = $this->brochureImageService->convertToWebpAndStore($file, 'item-brochures/');
                    $storedFilename = $result['storedFilename'];
                    $imagePath = $result['imagePath'];
                } catch (RuntimeException $e) {
                    Log::warning('Brochure image store failed', ['error' => $e->getMessage()]);
                    DB::rollBack();

                    return ApiResponse::failure('Image upload failed. Please try again.');
                }
            } elseif (! $request->existing) {
                DB::rollBack();

                return ApiResponse::failure('No image was provided.');
            }

            if (! $storedFilename || ! $imagePath) {
                DB::rollBack();

                return ApiResponse::failure('Image upload failed. Please try again.');
            }

            $existingImage = ItemBrochureImage::where('parent', $itemCode)->where('idx', $request->image_idx)->first();
            $updateData = [
                'modified' => $transactionDate,
                'modified_by' => Auth::user()->wh_user,
                'idx' => $request->image_idx,
                'image_filename' => $storedFilename,
                'image_path' => 'item-brochures/',
            ];

            if ($existingImage) {
                $existingImage->update($updateData);
            } else {
                ItemBrochureImage::insert([
                    'name' => uniqid(),
                    'creation' => $transactionDate,
                    'modified' => $transactionDate,
                    'modified_by' => Auth::user()->wh_user,
                    'owner' => Auth::user()->wh_user,
                    'parent' => $itemCode,
                    'idx' => $request->image_idx,
                    'image_filename' => $storedFilename,
                    'image_path' => 'item-brochures/',
                ]);
            }

            ProductBrochureLog::insert([
                'name' => uniqid(),
                'creation' => $transactionDate,
                'modified' => $transactionDate,
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'project' => $project,
                'filename' => $filename ?? $storedFilename,
                'created_by' => Auth::user()->wh_user,
                'transaction_date' => $transactionDate,
                'remarks' => 'For '.$itemCode,
                'transaction_type' => 'Upload Image',
            ]);

            DB::commit();

            $dataSrc = Storage::disk('upcloud')->url($imagePath.$storedFilename);

            Log::info('uploadImageForStandard success', [
                'item_code' => $itemCode,
                'image_idx' => $request->image_idx,
                'image_path' => $dataSrc,
            ]);

            return ApiResponse::successWith('Image uploaded.', ['src' => $dataSrc]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('uploadImageForStandard failed', [
                'error' => $e->getMessage(),
                'project' => $request->project,
                'item_code' => $request->item_code,
                'image_idx' => $request->image_idx,
                'existing' => (bool) $request->existing,
                'has_file' => $request->hasFile('selected-file'),
            ]);

            return ApiResponse::failure('Something went wrong. Please try again.');
        }
    }

    public function getItemAttributes($itemCode)
    {
        $attributes = ItemVariantAttribute::query()
            ->join('tabItem Attribute as attr', 'attr.name', 'tabItem Variant Attribute.attribute')
            ->where('tabItem Variant Attribute.parent', $itemCode)
            ->select('tabItem Variant Attribute.attribute', 'tabItem Variant Attribute.attribute_value', 'attr.name', 'attr.attr_name', 'tabItem Variant Attribute.hide_in_brochure')
            ->orderByRaw('LENGTH(`tabItem Variant Attribute`.`brochure_idx`) ASC')
            ->orderBy('tabItem Variant Attribute.brochure_idx', 'ASC')
            ->orderBy('tabItem Variant Attribute.idx')
            ->get();

        $remarks = Item::where('name', $itemCode)->value('item_brochure_remarks');

        return view('brochure.manage_item_attributes', compact('attributes', 'itemCode', 'remarks'));
    }

    public function updateBrochureAttributes(UpdateBrochureAttributesRequest $request)
    {
        DB::beginTransaction();
        try {
            $hiddenAttributes = collect($request->hidden_attributes)->filter()->values()->all();

            $this->brochureAttributeService->updateBrochureAttributes(
                $request->item_code,
                $request->attribute ?? [],
                $request->current_attribute,
                $hiddenAttributes,
                $request->remarks
            );

            DB::commit();

            return ApiResponse::success('Item Attributes updated.');
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::failure('Something went wrong. Please try again.');
        }
    }
}
