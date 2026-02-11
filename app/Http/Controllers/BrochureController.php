<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Item;
use App\Models\ItemAttribute;
use App\Models\ItemBrochureImage;
use App\Models\ItemImages;
use App\Models\ItemVariantAttribute;
use App\Models\ProductBrochureLog;
use App\Pipelines\BrochureUploadPipeline;
use App\Traits\GeneralTrait;
use Barryvdh\DomPDF\Facade\Pdf;
use Buglinjo\LaravelWebp\Facades\Webp;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;

class BrochureController extends Controller
{
    use GeneralTrait;

    public function __construct(
        protected BrochureUploadPipeline $brochureUploadPipeline
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

    public function readExcelFile(Request $request)
    {
        DB::beginTransaction();
        try {
            if (! $request->hasFile('selected-file')) {
                DB::rollBack();

                return ApiResponse::failure('No file uploaded.');
            }

            $attachedFile = $request->file('selected-file');

            if ($request->is_readonly) {
                $fileContents = $this->readFile($attachedFile);
                $content = $fileContents['content'];
                $project = isset($fileContents['project']) && $fileContents['project']
                    ? trim(str_replace('/', '-', $fileContents['project']))
                    : '-';
                $customer = $fileContents['customer'];
                $headers = $fileContents['headers'];

                return view('brochure.modal_product_list', compact('content', 'project', 'customer', 'headers'));
            }

            $passable = (object) [
                'request' => $request,
                'file' => $attachedFile,
                'readFileCallable' => fn ($file) => $this->readFile($file),
            ];

            $response = $this->brochureUploadPipeline->run($passable);
            DB::commit();

            return $response;
        } catch (Exception $e) {
            DB::rollBack();

            return ApiResponse::failure('Something went wrong. Please try again.');
        }
    }

    public function previewBrochure(Request $request, $project, $filename)
    {
        try {
            ini_set('max_execution_time', '300');
            $projectParam = trim($project);
            $file = storage_path('app/public/brochures/'.$projectParam.'/'.$filename);

            if (! Storage::disk('public')->exists('/brochures/'.$projectParam.'/'.$filename)) {
                return redirect('brochure')->with('error', 'File '.$filename.' does not exist.');
            }

            $fileContents = $this->readFile($file);

            $content = collect($fileContents['content'])->map(function ($q) {
                if ($q['id'] && collect($q['attributes'])->pluck('attribute_value')->filter()->values()->all()) {
                    return $q;
                }
            })->filter()->values()->all();
            $projectFromFile = trim($fileContents['project']);
            $project = $projectFromFile ?: $projectParam;
            $tableOfContents = $fileContents['table_of_contents'];

            if (isset($request->pdf) && $request->pdf) {
                $storage = Storage::disk('public')->files('/brochures/'.strtoupper($project));

                $series = null;
                if ($storage) {
                    $series = count($storage) > 1 ? count($storage) : 1;
                    $series = '-'.(string) $series;
                }

                $newFilename = Str::slug($project, '-').'-'.now()->format('Y-m-d').$series;

                $pdf = Pdf::loadView('brochure.pdf', compact('content', 'project', 'filename'));

                return $pdf->stream($newFilename.'.pdf');
            }

            return view('brochure.print_preview', compact('content', 'tableOfContents', 'project', 'filename'));
        } catch (\Throwable $th) {
            // throw $th;
            return redirect('brochure')->with('error', 'An error occured. Please try again.');
        }
    }

    public function uploadImage(Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->hasFile('selected-file')) {
                $file = $request->file('selected-file');
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG', 'webp', 'WEBP'];

                $folder = $request->project;
                $dir = $request->filename;

                $fileExt = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
                if (! in_array($fileExt, $allowedExtensions)) {
                    return ApiResponse::failure('Sorry, only .jpeg, .jpg and .png files are allowed.');
                }

                // get filename with extension
                $filenamewithextension = $file->getClientOriginalName();
                // //get filename without extension
                $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
                // get file extension
                $extension = $file->getClientOriginalExtension();
                // filename to store
                $microTime = round(microtime(true));

                $destinationPath = storage_path('/app/public/brochures/');

                $filename = $filename.'.'.$extension;

                $file->move($destinationPath, $filename);

                $excelFile = storage_path('/app/public/brochures/'.strtoupper($folder).'/'.$dir);

                $reader = new ReaderXlsx;
                $spreadsheet = $reader->load($excelFile);
                $sheet = $spreadsheet->getActiveSheet();
                // Get the highest row and column numbers referenced in the worksheet
                $highestColumn = $sheet->getHighestColumn();  // e.g 'F'
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);  // e.g. 5

                $row = $request->row;
                $column = null;
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $value = $sheet->getCell([$col, 4])->getValue();
                    if ($value == $request->column) {
                        $column = $col;
                        break;
                    }
                }

                $sheet->setCellValue([$column, $row], $filename);

                $writer = new WriterXlsx($spreadsheet);
                $writer->save($excelFile);

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

                $data = [
                    'src' => $filename,
                    'item_image_id' => $request->item_image_id,
                ];

                return ApiResponse::success('Image uploaded.', $data);
            }
        } catch (Exception $e) {
            DB::rollback();

            return ApiResponse::failure('Something went wrong. Please try again.');
        }

        return ApiResponse::failure('Something went wrong. Please try again.');
    }

    public function readFile($file)
    {
        $reader = new ReaderXlsx;
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getActiveSheet();

        // Get the highest row and column numbers referenced in the worksheet
        $highestRow = $sheet->getHighestRow();  // e.g. 10
        $highestColumn = $sheet->getHighestColumn();  // e.g 'F'

        // Get the highest row and column numbers referenced in the worksheet
        $highestRow = $sheet->getHighestRow();  // e.g. 10
        $highestColumn = $sheet->getHighestColumn();  // e.g 'F'

        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);  // e.g. 5

        $headerRowArr = [];
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $value = $sheet->getCell([$col, 4])->getValue();

            $headerRowArr[$col] = $value;
        }

        $content = $tableOfContents = $tblModal = $attrib = [];
        $project = $customer = null;
        for ($row = 5; $row <= $highestRow; $row++) {
            $result = $images = [];
            for ($col = 5; $col <= $highestColumnIndex; $col++) {
                $value = $sheet->getCell([$col, $row])->getValue();
                if (Arr::has($headerRowArr, $col)) {
                    $result[] = [
                        'attribute_name' => $headerRowArr[$col],
                        'attribute_value' => $value,
                    ];

                    $attrib[$headerRowArr[$col]] = $value != '-' ? $value : null;

                    if ($headerRowArr[$col] == 'Image 1') {
                        $images['image1'] = $sheet->getCell([$col, $row])->getValue();
                    }
                    if ($headerRowArr[$col] == 'Image 2') {
                        $images['image2'] = $sheet->getCell([$col, $row])->getValue();
                    }
                    if ($headerRowArr[$col] == 'Image 3') {
                        $images['image3'] = $sheet->getCell([$col, $row])->getValue();
                    }
                }
            }

            $itemName = $sheet->getCell([1, $row])->getValue();

            // for ajax table
            $attrib['Item Name'] = $itemName;
            $fittingType = $sheet->getCell([2, $row])->getValue();
            $attrib['Fitting Type'] = $fittingType != '-' ? $fittingType : null;
            $desc = $sheet->getCell([3, $row])->getValue();
            $attrib['Description'] = $desc != '-' ? $desc : null;
            $loc = $sheet->getCell([4, $row])->getValue();
            $attrib['Location'] = $loc != '-' ? $loc : null;
            // for ajax table

            $project = ! $project ? $sheet->getCell([2, 2])->getValue() : $project;
            $customer = ! $customer ? $sheet->getCell([2, 3])->getValue() : $customer;

            $content[] = [
                'id' => Str::slug($itemName, '-'),
                'row' => $row,
                'project' => $sheet->getCell([2, 2])->getValue(),
                'item_name' => $itemName,
                'images' => $images,
                'reference' => $sheet->getCell([2, $row])->getValue(),
                'description' => $sheet->getCell([3, $row])->getValue(),
                'location' => $sheet->getCell([4, $row])->getValue(),
                'attributes' => $result,
                'attrib' => $attrib,  // for ajax table
            ];

            $tableOfContents[] = [
                'id' => Str::slug($itemName, '-'),
                'text' => $itemName,
            ];
        }

        return [
            'content' => $content,
            'table_of_contents' => $tableOfContents,
            'project' => $project,
            'customer' => $customer,
            'headers' => $headerRowArr,
        ];
    }

    public function downloadBrochure($project, $file)
    {
        try {
            if (! Storage::disk('public')->exists('/brochures/'.strtoupper($project).'/'.$file)) {  // check if file exists
                return ApiResponse::failureLegacy('File not found');
            }

            $storage = Storage::disk('public')->files('/brochures/'.strtoupper($project));

            $series = null;
            if ($storage) {
                $series = count($storage) > 1 ? count($storage) : 1;
                $series = '-'.(string) $series;
            }

            $newFilename = Str::slug($project, '-').'-'.now()->format('Y-m-d').$series;
            $ext = explode('.', $file);
            $ext = isset($ext[1]) ? $ext[1] : 'xlsx';
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

            $folder = $request->project;
            $dir = $request->filename;
            $loc = $folder && $dir ? strtoupper($folder).'/'.$dir : null;

            if ($loc && Storage::disk('public')->exists('brochures/'.$loc)) {
                $excelFile = storage_path('/app/public/brochures/'.$loc);

                $reader = new ReaderXlsx;
                $spreadsheet = $reader->load($excelFile);
                $sheet = $spreadsheet->getActiveSheet();
                // Get the highest row and column numbers referenced in the worksheet
                $highestColumn = $sheet->getHighestColumn();  // e.g 'F'
                $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);  // e.g. 5

                $row = $request->row;
                $column = null;
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $value = $sheet->getCell([$col, 4])->getValue();
                    if ($value == $request->column) {
                        $column = $col;
                        break;
                    }
                }

                if ($column !== null) {
                    $sheet->setCellValue([$column, $row], null);
                }

                $writer = new WriterXlsx($spreadsheet);
                $writer->save($excelFile);

                DB::commit();
            }

            return ApiResponse::success('Image removed.');
        } catch (Exception $e) {
            DB::rollback();

            return ApiResponse::failure('Something went wrong. Please try again.');
        }
    }

    public function countBrochures()
    {
        $list = $itemCodes = [];
        if (session()->has('brochure_list')) {
            $list = session()->get('brochure_list.items');
            $list = isset($list) ? collect($list)->sortBy('idx')->toArray() : [];

            $itemCodes = collect($list)->pluck('item_code');
        }

        $itemArr = Item::whereIn('name', $itemCodes)->get();
        $itemArr = collect($itemArr)->groupBy('name');

        return view('brochure.brochure_floater', compact('itemArr', 'list'));
    }

    public function addToBrochureList(Request $request)
    {
        DB::beginTransaction();
        try {
            $save = isset($request->save) ? 1 : 0;
            $itemCodes = $request->item_codes ? $request->item_codes : [];
            $fittingType = $request->fitting_type ? $request->fitting_type : [];
            $location = $request->location ? $request->location : [];

            $idArr = $request->id_arr ? $request->id_arr : [];

            $itemBrochureDescription = $request->description ? $request->description : [];
            $itemBrochureName = $request->item_name ? $request->item_name : [];

            $project = $request->project ? $request->project : null;
            $customer = $request->customer ? $request->customer : null;

            $counter = session()->get('brochure_list.items');
            $counter = isset($counter) ? count($counter) + 1 : 1;

            session()->put('brochure_list.project', $project);
            session()->put('brochure_list.customer', $customer);

            foreach ($itemCodes as $idx => $itemCode) {
                if ($save) {
                    $id = isset($idArr[$idx]) ? $idArr[$idx] : $itemCode.'-0';
                } else {
                    $id = $itemCode.'-'.$counter;
                }

                $details = [
                    'item_code' => $itemCode,
                    'fitting_type' => isset($fittingType[$id]) ? $fittingType[$id] : null,
                    'location' => isset($location[$id]) ? $location[$id] : null,
                    'idx' => $idx,
                ];

                session()->put('brochure_list.items.'.$id, $details);

                if (isset($itemBrochureDescription[$itemCode]) || isset($itemBrochureName[$itemCode])) {
                    $update = [
                        'modified' => now()->toDateTimeString(),
                        'modified_by' => Auth::user()->wh_user,
                    ];

                    if (isset($itemBrochureDescription[$itemCode])) {
                        $update['item_brochure_description'] = $itemBrochureDescription[$itemCode];
                    }

                    if (isset($itemBrochureName[$itemCode])) {
                        $update['item_brochure_name'] = $itemBrochureName[$itemCode];
                    }

                    Item::where('name', $itemCode)->update($update);
                }
            }

            DB::commit();

            $showNotif = isset($request->generate_page) ? 0 : 1;

            return ApiResponse::successWith('Item added to list.', ['show_notif' => $showNotif]);
        } catch (\Throwable $th) {
            DB::rollback();

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

            $project = isset($session['project']) ? $session['project'] : null;
            $customer = isset($session['customer']) ? $session['customer'] : null;

            $itemCodes = collect($brochureList)->pluck('item_code');

            $itemDetailsQry = Item::whereIn('name', collect($brochureList)->pluck('item_code'))->get();
            $itemDetailsGroup = collect($itemDetailsQry)->groupBy('name');

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
                ->when($preview || $pdf, function ($q) {
                    return $q->where('tabItem Variant Attribute.hide_in_brochure', 0);
                })
                ->select('tabItem Variant Attribute.parent', 'tabItem Variant Attribute.attribute', 'tabItem Variant Attribute.attribute_value', 'attr.name', 'attr.attr_name', 'tabItem Variant Attribute.brochure_idx', 'tabItem Variant Attribute.hide_in_brochure')
                ->orderByRaw('LENGTH(`tabItem Variant Attribute`.`brochure_idx`) ASC')
                ->orderBy('tabItem Variant Attribute.brochure_idx', 'ASC')
                ->orderBy('tabItem Variant Attribute.idx')
                ->get();
            $attributeGroup = collect($attributesQry)->groupBy('parent');

            $currentItemImagesQry = ItemImages::whereIn('parent', $itemCodes)->get();
            $currentItemImagesGroup = collect($currentItemImagesQry)->groupBy('parent');

            $brochureImagesQry = ItemBrochureImage::whereIn('parent', $itemCodes)->select('parent', 'image_filename', 'idx', 'image_path', 'name')->orderByRaw('LENGTH(idx) ASC')->orderBy('idx', 'ASC')->get();
            $brochureImagesGroup = collect($brochureImagesQry)->groupBy('parent')->toArray();

            $content = [];
            $no = 1;
            foreach ($brochureList as $key => $details) {
                if (in_array($key, ['project', 'customer'])) {
                    continue;
                }

                $itemCode = $details['item_code'];

                $itemDetails = isset($itemDetailsGroup[$itemCode]) ? $itemDetailsGroup[$itemCode][0] : null;
                if (is_array($itemDetails)) {
                    $itemDetails = (object) $itemDetails;
                }
                if (! $itemDetails) {
                    continue;
                }
                /** @var object $itemDetails */
                $attributes = isset($attributeGroup[$itemCode]) ? $attributeGroup[$itemCode] : [];
                $currentItemImages = isset($currentItemImagesGroup[$itemCode]) ? $currentItemImagesGroup[$itemCode] : [];
                $brochureImages = isset($brochureImagesGroup[$itemCode]) ? $brochureImagesGroup[$itemCode] : [];

                $itemName = $itemDetails->item_brochure_name ? $itemDetails->item_brochure_name : $itemDetails->item_name;
                $itemDescription = $itemDetails->item_brochure_description ? $itemDetails->item_brochure_description : $itemDetails->description;

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
                    $base64 = $this->base64Image("/img/$filename");

                    $currentImages[] = [
                        'filename' => $filename,
                        'filepath' => "storage/img/$filename",
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

            $fumacoLogo = asset('storage/fumaco_logo.png');

            if ($preview) {
                return view('brochure.preview_loop', compact('content', 'project', 'customer', 'fumacoLogo'));
            }

            if ($pdf) {
                $isStandard = true;
                $filename = Str::slug($project, '-');
                $newFilename = Str::slug($project, '-').'-'.now()->format('Y-m-d');
                $remarks = '';

                $pdf = Pdf::loadView('brochure.pdf', compact('content', 'project', 'filename', 'isStandard', 'remarks'));

                return $pdf->stream($newFilename.'.pdf');
            }

            return view('brochure.multiple_brochure', compact('content', 'project', 'customer'));
        } catch (\Throwable $th) {
            // throw $th;
            return redirect()->back()->with('error', 'An error occured. Please try again.');
        }
    }

    // /generate_brochure
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

            $currentItemImages = ItemImages::where('parent', $data['item_code'])->get();
            $currentImages = [];
            foreach ($currentItemImages as $e) {
                $filename = $e->image_path;
                if (! Storage::disk('public')->exists('img/'.$filename) && $filename) {
                    $filename = explode('.', $filename)[0].'.webp';
                }
                // $base64 = $this->base64Image('/img/'.$filename);

                $currentImages[] = [
                    'filename' => $filename,
                    'filepath' => 'storage/img/'.$filename,
                ];
            }

            $brochureImages = ItemBrochureImage::where('parent', $data['item_code'])->select('image_filename', 'idx', 'image_path', 'name')->orderByRaw('LENGTH(idx) ASC')->orderBy('idx', 'ASC')->get();

            for ($i = 0; $i < 3; $i++) {
                $row = $i + 1;
                $filepath = null;
                if (isset($brochureImages[$i])) {
                    $filepath = $brochureImages[$i]->image_path.$brochureImages[$i]->image_filename;
                    $filepath = asset($filepath);
                }
                $images['image'.$row] = [
                    'id' => isset($brochureImages[$i]) ? $brochureImages[$i]->name : null,
                    'filepath' => $filepath,
                ];
            }

            $fumacoLogo = asset('storage/fumaco_logo.png');

            if (isset($request->get_images) && $request->get_images) {
                return view('brochure.brochure_images', compact('images', 'currentImages'));
            }

            if (isset($request->pdf) && $request->pdf) {
                $newFilename = Str::slug($request->item_name, '-').'-'.now()->format('Y-m-d');
                $project = $request->project;
                $filename = $request->filename;

                $attrib = [];
                foreach ($attributes as $att) {
                    $attrib[$att->attribute] = $att->attribute_value;
                    $attributesArr[] = [
                        'attribute_name' => $att->attr_name ? $att->attr_name : $att->attribute,
                        'attribute_value' => $att->attribute_value,
                    ];
                }

                Item::where('name', $request->item_code)->update([
                    'item_brochure_name' => $request->item_name,
                    'item_brochure_description' => $request->description,
                    'modified' => now()->toDateTimeString(),
                    'modified_by' => Auth::user()->wh_user,
                ]);

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

                $isStandard = true;
                DB::commit();

                $pdf = Pdf::loadView('brochure.pdf', compact('content', 'project', 'filename', 'isStandard', 'remarks', 'fumacoLogo'));

                return $pdf->stream($newFilename.'.pdf');
            }

            $imgCheck = collect($currentImages)->map(function ($q) {
                return Storage::disk('public')->exists($q['filepath']) ? 1 : 0;
            })->max();

            return view('brochure.preview_standard_brochure', compact('data', 'attributes', 'images', 'currentImages', 'imgCheck', 'remarks', 'fumacoLogo'));
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function uploadImageForStandard(Request $request)
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
                $base = pathinfo($filename, PATHINFO_FILENAME);
                $webpFilename = $base.'.webp';
                $imagePath = 'img/';
                $storedFilename = Storage::disk('public')->exists($imagePath.$webpFilename)
                    ? $webpFilename
                    : $filename;
            }

            if ($request->hasFile('selected-file')) {
                $file = $request->file('selected-file');
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG', 'webp', 'WEBP'];

                $fileExt = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

                if (! in_array($fileExt, $allowedExtensions)) {
                    return ApiResponse::failure('Sorry, only .jpeg, .jpg and .png files are allowed.');
                }

                $filenamewithextension = $file->getClientOriginalName();
                $filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
                $filename = str_replace(' ', '-', $filename);
                $extension = $file->getClientOriginalExtension();

                // Paths for storage
                $imagePath = 'brochures/';
                // $jpegFilename = "$filename.$extension";
                $webpFilename = "$filename.webp";

                // Save the original file
                // Storage::putFileAs($imagePath, $file, $jpegFilename);

                // Create and save the WebP version
                $shouldCleanupWebp = false;
                try {
                    if (strtolower($fileExt) != 'webp') {
                        $webp = Webp::make($file);

                        if (! File::exists(public_path('temp'))) {
                            File::makeDirectory(public_path('temp'), 0755, true);
                        }

                        $webpPath = public_path("temp/$webpFilename");
                        $webp->save($webpPath);
                        $shouldCleanupWebp = true;
                    } else {
                        $webpPath = $file->getPathname();
                    }

                    $webStream = fopen($webpPath, 'r');
                    Storage::disk('public')->put("$imagePath$webpFilename", $webStream);
                    if (is_resource($webStream)) {
                        fclose($webStream);
                    }
                    $storedFilename = $webpFilename;

                    if ($shouldCleanupWebp && File::exists($webpPath)) {
                        unlink($webpPath);
                    }
                } catch (\Throwable $e) {
                    Log::warning('WebP conversion failed, saving original image', [
                        'error' => $e->getMessage(),
                        'original_name' => $filenamewithextension,
                    ]);

                    $storedFilename = "$filename.$extension";
                    $fallbackStream = fopen($file->getPathname(), 'r');
                    Storage::disk('public')->put("$imagePath$storedFilename", $fallbackStream);
                    if (is_resource($fallbackStream)) {
                        fclose($fallbackStream);
                    }
                }
            } elseif (! $request->existing) {
                return ApiResponse::failure('No image was provided.');
            }

            if (! $storedFilename || ! $imagePath) {
                return ApiResponse::failure('Image upload failed. Please try again.');
            }

            $existingImageIdx = ItemBrochureImage::where('parent', $itemCode)->where('idx', $request->image_idx)->first();
            if ($existingImageIdx) {
                ItemBrochureImage::where('name', $existingImageIdx->name)->update([
                    'modified' => $transactionDate,
                    'modified_by' => Auth::user()->wh_user,
                    'idx' => $request->image_idx,
                    'image_filename' => $storedFilename,
                ]);
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
                    'image_path' => "storage/$imagePath",
                ]);
            }

            ProductBrochureLog::insert([
                'name' => uniqid(),
                'creation' => $transactionDate,
                'modified' => $transactionDate,
                'modified_by' => Auth::user()->wh_user,
                'owner' => Auth::user()->wh_user,
                'project' => $project,
                'filename' => $filename,
                'created_by' => Auth::user()->wh_user,
                'transaction_date' => $transactionDate,
                'remarks' => 'For '.$itemCode,
                'transaction_type' => 'Upload Image',
            ]);

            DB::commit();

            $dataSrc = "storage/$imagePath$storedFilename";

            Log::info('uploadImageForStandard success', [
                'item_code' => $itemCode,
                'image_idx' => $request->image_idx,
                'image_path' => $dataSrc,
            ]);

            return ApiResponse::successWith('Image uploaded.', ['src' => $dataSrc]);
        } catch (\Throwable $e) {
            DB::rollback();
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

    public function updateBrochureAttributes(Request $request)
    {
        DB::beginTransaction();
        try {
            $transactionDate = now()->toDateTimeString();
            $requestAttributes = $request->attribute;
            $currentAttributes = $request->current_attribute;
            $hiddenAttributes = collect($request->hidden_attributes)->filter()->values()->all();
            $idx = 0;
            foreach ($requestAttributes as $attributeName => $newAttributeName) {
                if ($currentAttributes[$attributeName] != $newAttributeName) {
                    ItemAttribute::where('name', $attributeName)->update([
                        'attr_name' => $newAttributeName,
                        'modified' => $transactionDate,
                        'modified_by' => Auth::user()->wh_user,
                    ]);
                }
            }

            foreach ($currentAttributes as $name => $attribute) {
                ItemVariantAttribute::where('parent', $request->item_code)->where('attribute', $attribute)->update([
                    'brochure_idx' => $idx += 1,
                    'hide_in_brochure' => in_array($attribute, $hiddenAttributes) ? 1 : 0,
                    'modified_by' => Auth::user()->wh_user,
                    'modified' => now()->toDateTimeString(),
                ]);
            }

            Item::where('name', $request->item_code)->update([
                'item_brochure_remarks' => $request->remarks,
                'modified' => now()->toDateTimeString(),
                'modified_by' => Auth::user()->wh_user,
            ]);

            DB::commit();

            return ApiResponse::success('Item Attributes updated.');
        } catch (Exception $e) {
            DB::rollback();

            return ApiResponse::failure('Something went wrong. Please try again.');
        }
    }
}
