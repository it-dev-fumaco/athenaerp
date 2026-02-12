<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon; 
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Support\Facades\File;
use Buglinjo\LaravelWebp\Facades\Webp;

use App\Traits\GeneralTrait;

class BrochureController extends Controller
{

	use GeneralTrait;
    public function viewForm(Request $request) {
		if($request->ajax()){
			$recents = DB::table('tabProduct Brochure Log')
				->where('transaction_type', 'Upload Excel File')
				->when($request->search, function ($q) use ($request){
					return $q->where('project', 'like', '%'.$request->search.'%')->orWhere('filename', 'like', '%'.$request->search.'%');
				})
				->select(DB::raw('MAX(transaction_date) as transaction_date'), 'project', 'filename', DB::raw('MIN(created_by) as created_by'))
				->groupBy('project', 'filename')->orderBy('creation', 'desc')->limit(10)->get();

			$recent_uploads = [];
			foreach ($recents as $row) {
				$seconds = Carbon::now()->diffInSeconds(Carbon::parse($row->transaction_date));
				$minutes = Carbon::now()->diffInMinutes(Carbon::parse($row->transaction_date));
				$hours = Carbon::now()->diffInHours(Carbon::parse($row->transaction_date));
				$days = Carbon::now()->diffInDays(Carbon::parse($row->transaction_date));
				$months = Carbon::now()->diffInMonths(Carbon::parse($row->transaction_date));
				$years = Carbon::now()->diffInYears(Carbon::parse($row->transaction_date));

				$duration = '';
				if ($minutes <= 59) {
					$duration = $minutes . 'm ago';
				}

				if ($seconds <= 59) {
					$duration = $seconds . 's ago';
				}

				if ($hours >= 1) {
					$duration = $hours . 'h ago';
				}

				if ($days >= 1) {
					$duration = $days . 'd ago';
				}

				if ($months >= 1) {
					$duration = $months . 'm ago';
				}

				if ($years >= 1) {
					$duration = $years . 'y ago';
				}

				$recent_uploads[] = [
					'project' => $row->project,
					'filename' => $row->filename,
					'created_by' => $row->created_by,
					'duration' => $duration,
				];
			}

			return view('brochure.history', compact('recent_uploads'));
		}

        return view('brochure.form');
    }

    public function readExcelFile(Request $request){
		DB::beginTransaction();
        try {
            if($request->hasFile('selected-file')){
				$attached_file = $request->file('selected-file');

				$allowed_extensions = ['xlsx', 'xls', 'XLSX', 'XLS'];

                $file_name = pathinfo($attached_file->getClientOriginalName(), PATHINFO_FILENAME);
				$file_ext = pathinfo($attached_file->getClientOriginalName(), PATHINFO_EXTENSION);

				if(!in_array($file_ext, $allowed_extensions)){
					return response()->json(['status' => 0, 'message' => 'Sorry, only .xlsx and .xls files are allowed.']);
				}

				$file_contents = $this->readFile($attached_file);

				$content = $file_contents['content'];
				$project = isset($file_contents['project']) && $file_contents['project'] ? trim(str_replace('/', '-', $file_contents['project'])) : '-';
				$customer = $file_contents['customer'];
				$headers = $file_contents['headers'];
				$table_of_contents = $file_contents['table_of_contents'];

				if($request->is_readonly){
					return view('brochure.modal_product_list', compact('content', 'project', 'customer', 'headers'));
				}

				$transaction_date = Carbon::now()->toDateTimeString();

				if(!Storage::disk('public')->exists('/brochures/'.strtoupper($project))){
					Storage::disk('public')->makeDirectory('/brochures/'.strtoupper($project));
				}

				$storage = Storage::disk('public')->files('/brochures/'.strtoupper($project));

				$series = null;
				if($storage){
					$series = count($storage) > 1 ? count($storage) : 1;
					$series = '-'.(string)$series;
				}

				$new_filename = Str::slug($project, '-').'-'.Carbon::now()->format('Y-m-d').$series;

				DB::table('tabProduct Brochure Log')->insert([
					'name' => uniqid(),
					'creation' => $transaction_date,
					'modified' => $transaction_date,
					'modified_by' => $request->ip(),
					'owner' => $request->ip(),
					'project' => $project,
					'filename' => $new_filename. '.' . $file_ext,
					'created_by' => $request->ip(),
					'transaction_date' => $transaction_date,
					'remarks' => null,
					'transaction_type' => 'Upload Excel File'
				]);
				
				$attached_file->move(public_path('storage/brochures/'.strtoupper($project)), $new_filename. '.' . $file_ext);

				DB::commit();

				return response()->json(['status' => 1, 'message' => '/preview/' . strtoupper($project) . '/' . $new_filename. '.' . $file_ext]);
			}
		} catch (Exception $e) {
			DB::rollback();

			return response()->json(['status' => 0, 'message' => 'Something went wrong. Please try again.']);
        }
	}

	public function previewBrochure(Request $request, $project, $filename) {
		try {
			ini_set('max_execution_time', '300');
			$project_param = trim($project);
			$file = storage_path('app/public/brochures/'. $project_param .'/'. $filename);

			if(!Storage::disk('public')->exists('/brochures/'. $project_param .'/'. $filename)){
				return redirect('brochure')->with('error', 'File '.$filename.' does not exist.');
			}

			$file_contents = $this->readFile($file);

			$content = collect($file_contents['content'])->map(function ($q){
				if($q['id'] && collect($q['attributes'])->pluck('attribute_value')->filter()->values()->all()){
					return $q;
				}
			})->filter()->values()->all();
			$project_from_file = trim($file_contents['project']);
			$project = $project_from_file ?: $project_param;
			$table_of_contents = $file_contents['table_of_contents'];

			if(isset($request->pdf) && $request->pdf){
				$storage = Storage::disk('public')->files('/brochures/'.strtoupper($project));

				$series = null;
				if($storage){
					$series = count($storage) > 1 ? count($storage) : 1;
					$series = '-'.(string)$series;
				}

				$new_filename = Str::slug($project, '-').'-'.Carbon::now()->format('Y-m-d').$series;

				// Resolve image paths and embed as base64 so PDF always finds uploaded images
				$content = $this->resolveBrochureImagePathsForPdf($content, $project, $is_standard = false);

				$pdf = Pdf::loadView('brochure.pdf', compact('content', 'project', 'filename'));
				return $pdf->stream($new_filename.'.pdf');
			}

			return view('brochure.print_preview', compact('content', 'table_of_contents', 'project', 'filename'));
		} catch (\Throwable $th) {
			// throw $th;
			return redirect('brochure')->with('error', 'An error occured. Please try again.');
		}
		
	}

	public function uploadImage(Request $request) {
		DB::beginTransaction();
		try {
			if($request->hasFile('selected-file')){
				$file = $request->file('selected-file');
				$allowed_extensions = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG', 'webp', 'WEBP'];
	
				$folder = $request->project;
				$dir = $request->filename;
	
				$file_ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
				if(!in_array($file_ext, $allowed_extensions)){
					return response()->json(['status' => 0, 'message' => 'Sorry, only .jpeg, .jpg and .png files are allowed.']);
				}
	
				//get filename with extension
				$filenamewithextension = $file->getClientOriginalName();
				// //get filename without extension
				$filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
				//get file extension
				$extension = $file->getClientOriginalExtension();
				//filename to store
				$micro_time = round(microtime(true));
		
				$destinationPath = storage_path('/app/public/brochures/');
	
				$filename = $filename . '.' . $extension;
	
				$file->move($destinationPath, $filename);

				$excel_file = storage_path('/app/public/brochures/'.strtoupper($folder).'/'. $dir);
				
				$reader = new ReaderXlsx();
				$spreadsheet = $reader->load($excel_file);
				$sheet = $spreadsheet->getActiveSheet();
				// Get the highest row and column numbers referenced in the worksheet
				$highestColumn = $sheet->getHighestColumn(); // e.g 'F'
				$highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); // e.g. 5
	
				$row = $request->row;
				$column = null;
				for ($col = 1; $col <= $highestColumnIndex; $col++) {
					$value = $sheet->getCellByColumnAndRow($col, 4)->getValue();
					if ($value == $request->column) {
						$column = $col;
						break;
					}
				}
				
				$sheet->setCellValueByColumnAndRow($column, $row, $filename);
	
				$writer = new WriterXlsx($spreadsheet);
				$writer->save($excel_file);
	
				$transaction_date = Carbon::now()->toDateTimeString();

				DB::table('tabProduct Brochure Log')->insert([
					'name' => uniqid(),
					'creation' => $transaction_date,
					'modified' => $transaction_date,
					'modified_by' => $request->ip(),
					'owner' => $request->ip(),
					'project' => $folder,
					'filename' => $filename,
					'created_by' => $request->ip(),
					'transaction_date' => $transaction_date,
					'remarks' => 'For ' . $dir,
					'transaction_type' => 'Upload Image'
				]);

				$existing = DB::table('tabItem Brochure Image')
					->where('parent', $request->item_code)
					->where('idx', $request->image_idx)
					->first();
				
				if ($existing) {
					DB::table('tabItem Brochure Image')
						->where('name', $existing->name)
						->update([
							'modified' => $transaction_date,
							'image_filename' => $filename,
							'image_path' => 'storage/brochures/'.$filename
						]);
				} else {
					DB::table('tabItem Brochure Image')->insert([
						'name' => uniqid(),
						'parent' => $request->item_code,
						'idx' => $request->image_idx,
						'creation' => $transaction_date,
						'modified' => $transaction_date,
						'modified_by' => $request->ip(),
						'owner' => $request->ip(),
						'image_filename' => $filename,
						'image_path' => 'storage/brochures/'.$filename
					]);
				}
				
				DB::commit();

				$data = [
					'src' => $filename,
					'item_image_id' => $request->item_image_id
				];
	
				return response()->json(['status' => 1, 'message' => 'Image uploaded.', 'data' => $data]);
			}
		} catch (Exception $e) {
			DB::rollback();

			return response()->json([
				'status' => 0,
				'message' => $e->getMessage(),
				'line' => $e->getLine(),
				'file' => $e->getFile()
			]);
		}
		
		return response()->json(['status' => 0, 'message' => 'Something went wrong. Please try again.']);
	}

	public function readFile($file) {
		$reader = new ReaderXlsx();
		$spreadsheet = $reader->load($file);
		$sheet = $spreadsheet->getActiveSheet();

		// Get the highest row and column numbers referenced in the worksheet
		$highestRow = $sheet->getHighestRow(); // e.g. 10
		$highestColumn = $sheet->getHighestColumn(); // e.g 'F'

		// Get the highest row and column numbers referenced in tdeleteImagehe worksheet
		$highestRow = $sheet->getHighestRow(); // e.g. 10
		$highestColumn = $sheet->getHighestColumn(); // e.g 'F'

		$highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); // e.g. 5

		$headerRowArr = [];
		for ($col = 1; $col <= $highestColumnIndex; $col++) {
			$value = $sheet->getCellByColumnAndRow($col, 4)->getValue();

			$headerRowArr[$col] = $value;
		}

		$content = $table_of_contents = $tbl_modal = $attrib = [];
		$project = $customer = null;
		for ($row = 5; $row <= $highestRow; $row++) {
			$result = $images = [];
			for ($col = 5; $col <= $highestColumnIndex; $col++) {
				$value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
				if(array_key_exists($col, $headerRowArr)){
					$result[] = [
						'attribute_name' => $headerRowArr[$col],
						'attribute_value' => $value
					];

					$attrib[$headerRowArr[$col]] = $value != '-' ? $value : null;
				}
			}

			$item_name = $sheet->getCellByColumnAndRow(1, $row)->getValue();

			$item_code = Str::slug($item_name, '-');

			$db_images = DB::table('tabItem Brochure Image')
				->where('parent', $item_code)
				->orderBy('idx')
				->get();

			$images = [];

			foreach ($db_images as $img) {
				$images['image'.$img->idx] = $img->image_filename;
			}

			// for ajax table
			$attrib['Item Name'] = $item_name;
			$attrib['Fitting Type'] = $sheet->getCellByColumnAndRow(2, $row)->getValue() != '-' ? $sheet->getCellByColumnAndRow(2, $row)->getValue() : null;
			$attrib['Description'] = $sheet->getCellByColumnAndRow(3, $row)->getValue() != '-' ? $sheet->getCellByColumnAndRow(3, $row)->getValue() : null;
			$attrib['Location'] = $sheet->getCellByColumnAndRow(4, $row)->getValue() != '-' ? $sheet->getCellByColumnAndRow(4, $row)->getValue() : null;
			// for ajax table

			$project = !$project ? $sheet->getCellByColumnAndRow(2, 2)->getValue() : $project;
			$customer = !$customer ? $sheet->getCellByColumnAndRow(2, 3)->getValue() : $customer;

			$content[] = [
				'id' => $item_code,
				'row' => $row,
				'project' => $sheet->getCellByColumnAndRow(2, 2)->getValue(),
				'item_name' => $item_name,
				'images' => $images,
				'reference' => $sheet->getCellByColumnAndRow(2, $row)->getValue(),
				'description' => $sheet->getCellByColumnAndRow(3, $row)->getValue(),
				'location' => $sheet->getCellByColumnAndRow(4, $row)->getValue(),
				'attributes' => $result,
				'attrib' => $attrib // for ajax table
			];

			$table_of_contents[] = [
				'id' => Str::slug($item_name, '-'),
				'text' => $item_name
			];
		}

		return [
			'content' => $content,
			'table_of_contents' => $table_of_contents,
			'project' => $project,
			'customer' => $customer,
			'headers' => $headerRowArr,
		];
	}

	public function downloadBrochure($project, $file){
		try{
			if(!Storage::disk('public')->exists('/brochures/'.strtoupper($project).'/'.$file)){ // check if file exists
				return response()->json(['success' => 0, 'message' => 'File not found']);
			}

			$storage = Storage::disk('public')->files('/brochures/'.strtoupper($project));

			$series = null;
			if($storage){
				$series = count($storage) > 1 ? count($storage) : 1;
				$series = '-'.(string)$series;
			}

			$new_filename = Str::slug($project, '-').'-'.Carbon::now()->format('Y-m-d').$series;
			$ext = explode('.', $file);
			$ext = isset($ext[1]) ? $ext[1] : 'xlsx';
			$new_name = $new_filename.'.'.$ext;

			$orig_path = strtoupper($project).'/'.$file;

			return response()->json([
				'success' => 1,
				'new_name' => $new_name,
				'orig_path' => $orig_path
			]);
		}catch (Exception $e){
			return response()->json(['success' => 0, 'message' => 'Something went wrong. Please try again.']);
		}
	}

	public function removeImage(Request $request)
	{
		DB::beginTransaction();
	
		try {
			$image = DB::table('tabItem Brochure Image')
				->where('parent', $request->item_code)
				->where('idx', $request->image_idx)
				->first();
	
			if (!$image) {
				return response()->json([
					'status' => 0,
					'message' => 'Image not found.'
				]);
			}
	
			// Delete physical file
			Storage::disk('public')->delete('brochures/'.$image->image_filename);
	
			// Delete DB row
			DB::table('tabItem Brochure Image')
				->where('parent', $request->item_code)
				->where('idx', $request->image_idx)
				->delete();
	
			DB::commit();
	
			return response()->json([
				'status' => 1,
				'message' => 'Image removed.'
			]);
	
		} catch (Exception $e) {
	
			DB::rollback();
	
			return response()->json([
				'status' => 0,
				'message' => $e->getMessage()
			]);
		}
	}

	public function countBrochures(){
		$list = $item_codes = [];
		if(session()->has('brochure_list')){
			$list = session()->get('brochure_list.items');
			$list = isset($list) ? collect($list)->sortBy('idx')->toArray() : [];

			$item_codes = collect($list)->pluck('item_code');
		}

		$item_arr = DB::table('tabItem')->whereIn('name', $item_codes)->get();
		$item_arr = collect($item_arr)->groupBy('name');

		return view('brochure.brochure_floater', compact('item_arr', 'list'));
	}

	public function addToBrochureList(Request $request){
		DB::beginTransaction();
		try {
			$save = isset($request->save) ? 1 : 0;
			$item_codes = $request->item_codes ? $request->item_codes : [];
			$fitting_type = $request->fitting_type ? $request->fitting_type : [];
			$location = $request->location ? $request->location : [];

			$id_arr = $request->id_arr ? $request->id_arr : [];

			$item_brochure_description = $request->description ? $request->description : [];
			$item_brochure_name = $request->item_name ? $request->item_name : [];

			$project = $request->project ? $request->project : null;
			$customer = $request->customer ? $request->customer : null;

			$counter = session()->get('brochure_list.items');
			$counter = isset($counter) ? count($counter) + 1 : 1;

			session()->put('brochure_list.project', $project);
			session()->put('brochure_list.customer', $customer);

			foreach ($item_codes as $idx => $item_code) {
				if($save){
					$id = isset($id_arr[$idx]) ? $id_arr[$idx] : $item_code.'-0';
				}else{
					$id = $item_code.'-'.$counter;
				}

				$details = [
					'item_code' => $item_code,
					'fitting_type' => isset($fitting_type[$id]) ? $fitting_type[$id] : null,
					'location' => isset($location[$id]) ? $location[$id] : null,
					'idx' => $idx
				];
				
				session()->put('brochure_list.items.'.$id, $details);

				if(isset($item_brochure_description[$item_code]) || isset($item_brochure_name[$item_code])){
					$update = [
						'modified' => Carbon::now()->toDateTimeString(),
						'modified_by' => Auth::user()->wh_user
					];

					if(isset($item_brochure_description[$item_code])){
						$update['item_brochure_description'] = $item_brochure_description[$item_code];
					}

					if(isset($item_brochure_name[$item_code])){
						$update['item_brochure_name'] = $item_brochure_name[$item_code];
					}

					DB::table('tabItem')->where('name', $item_code)->update($update);
				}
			}

			DB::commit();
			
			$show_notif = isset($request->generate_page) ? 0 : 1;

			return response()->json(['status' => 1, 'message' => 'Item added to list.', 'show_notif' => $show_notif]);
		} catch (\Throwable $th) {
			DB::rollback();
			return response()->json(['status' => 0, 'message' => 'An error occured. Please try again.']);
		}
	}

	public function removeFromBrochureList($key){
		session()->forget('brochure_list.items.'.$key);
	}

	public function generateMultipleBrochures(Request $request){
		DB::beginTransaction();
		try {
			$session = session()->get('brochure_list');
			$brochure_list = isset($session['items']) ? collect($session['items'])->sortBy('idx')->toArray() : [];

			$project = isset($session['project']) ? $session['project'] : null;
			$customer = isset($session['customer']) ? $session['customer'] : null;

			$item_codes = collect($brochure_list)->pluck('item_code');

			$item_details_qry = DB::table('tabItem')->whereIn('name', collect($brochure_list)->pluck('item_code'))->get();
			$item_details_group = collect($item_details_qry)->groupBy('name');

			$preview = isset($request->preview) && $request->preview ? 1 : 0;
			$pdf = isset($request->pdf) && $request->pdf ? 1 : 0;

			if($pdf){
				set_time_limit(300);
				ini_set('max_execution_time', 3600);
				ini_set('memory_limit', '4096M');
			}

			$attributes_qry = DB::table('tabItem Variant Attribute as variant')
				->join('tabItem Attribute as attr', 'attr.name', 'variant.attribute')
				->whereIn('variant.parent', $item_codes)
				->when($preview || $pdf, function($q){
					return $q->where('variant.hide_in_brochure', 0);
				})
				->select('variant.parent', 'variant.attribute', 'variant.attribute_value', 'attr.name', 'attr.attr_name', 'variant.brochure_idx', 'variant.hide_in_brochure')
				->orderByRaw('LENGTH(variant.brochure_idx) ASC')->orderBy('variant.brochure_idx', 'ASC')->orderBy('variant.idx')->get();
			$attribute_group = collect($attributes_qry)->groupBy('parent');

			$current_item_images_qry = DB::table('tabItem Images')->whereIn('parent', $item_codes)->get();
			$current_item_images_group = collect($current_item_images_qry)->groupBy('parent');

			$brochure_images_qry = DB::table('tabItem Brochure Image')->whereIn('parent', $item_codes)->select('parent', 'image_filename', 'idx', 'image_path', 'name')->orderByRaw('LENGTH(idx) ASC')->orderBy('idx', 'ASC')->get();
			$brochure_images_group = collect($brochure_images_qry)->groupBy('parent')->toArray();

			$content = [];
			$no = 1;
			foreach($brochure_list as $key => $details){
				if(in_array($key, ['project', 'customer'])){
					continue;
				}

				$item_code = $details['item_code'];

				$item_details = isset($item_details_group[$item_code]) ? $item_details_group[$item_code][0] : [];
				$attributes = isset($attribute_group[$item_code]) ? $attribute_group[$item_code] : [];
				$current_item_images = isset($current_item_images_group[$item_code]) ? $current_item_images_group[$item_code] : [];
				$brochure_images = isset($brochure_images_group[$item_code]) ? $brochure_images_group[$item_code] : [];

				$item_name = $item_details->item_brochure_name ? $item_details->item_brochure_name : $item_details->item_name;
				$item_description = $item_details->item_brochure_description ? $item_details->item_brochure_description : $item_details->description;

				$attrib = [];
				$attributes_arr = [];
				foreach ($attributes as $att) {
					$attrib[$att->attribute] = $att->attribute_value;
					$attributes_arr[] = [
						'attribute_name' => $att->attr_name ? $att->attr_name : $att->attribute,
						'attribute_value' => $att->attribute_value
					];
				}

				$current_images = [];
				foreach ($current_item_images as $e) {
					$filename = $e->image_path;
					$base64 = $this->base64_image("/img/$filename");
	
					$current_images[] = [
						'filename' => $filename,
						'filepath' => "storage/img/$filename"
					];	
				}

				$images = [];
				for($i = 0; $i < 3; $i++){
					$row = $i + 1;
					$images['image'.$row] = [
						'id' => isset($brochure_images[$i]) ? $brochure_images[$i]->name : null,
						'filepath' => isset($brochure_images[$i]) ? $brochure_images[$i]->image_path.$brochure_images[$i]->image_filename : null,
					];
				}

				$content[] = [
					'item_code' => $item_code,
					'id' => Str::slug($item_name, '-'),
					'row' => $i + 1,
					'project' => $project,
					'item_name' => $item_name,
					'reference' => $details['fitting_type'],
					'description' => $item_description,
					'location' => $details['location'],
					'current_images' => $current_images,
					'images' => $images,
					'attributes' => $attributes_arr,
					'attrib' => $attrib,
					'remarks' => $item_details->item_brochure_remarks,
					'key' => $key,
					'idx' => $no++
				];
			}

			$fumaco_logo = asset('storage/fumaco_logo.png');

			if($preview){
				return view('brochure.preview_loop', compact('content', 'project', 'customer', 'fumaco_logo'));
			}

			if($pdf){
				$is_standard = true;
				$filename = Str::slug($project, '-');
				$new_filename = Str::slug($project, '-').'-'.Carbon::now()->format('Y-m-d');
				$remarks = '';

				$content = $this->resolveBrochureImagePathsForPdf($content, $project, true);

				$pdf = Pdf::loadView('brochure.pdf', compact('content', 'project', 'filename', 'is_standard', 'remarks'));
				return $pdf->stream($new_filename.'.pdf');
			}

			return view('brochure.multiple_brochure', compact('content', 'project', 'customer'));
		} catch (\Throwable $th) {
			// throw $th;
			return redirect()->back()->with('error', 'An error occured. Please try again.');
		}
	}

	// /generate_brochure
	public function generateBrochure(Request $request) {
		DB::beginTransaction();
		try {
			ini_set('max_execution_time', '300');
			$data = $request->all();

			$attributes = DB::table('tabItem Variant Attribute as variant')
				->join('tabItem Attribute as attr', 'attr.name', 'variant.attribute')
				->where('variant.parent', $data['item_code'])->where('hide_in_brochure', 0)
				->select('variant.attribute', 'variant.attribute_value', 'attr.name', 'attr.attr_name')
				->orderByRaw('LENGTH(variant.brochure_idx) ASC')->orderBy('variant.brochure_idx', 'ASC')->orderBy('variant.idx')->get();
				
			$remarks = DB::table('tabItem')->where('name', $data['item_code'])->pluck('item_brochure_remarks')->first();

			$current_item_images = DB::table('tabItem Images')->where('parent', $data['item_code'])->get();
			$current_images = [];
			foreach ($current_item_images as $e) {
				$filename = $e->image_path;
				if(!Storage::disk('public')->exists('img/' . $filename) && $filename){
					$filename = explode(".", $filename)[0] . '.webp';
				}
				// $base64 = $this->base64_image('/img/'.$filename);

				$current_images[] = [
					'filename' => $filename,
					'filepath' => 'storage/img/' . $filename
				];	
			}

			$brochure_images = DB::table('tabItem Brochure Image')->where('parent', $data['item_code'])->select('image_filename', 'idx', 'image_path', 'name')->orderByRaw('LENGTH(idx) ASC')->orderBy('idx', 'ASC')->get();

			for($i = 0; $i < 3; $i++){
				$row = $i + 1;
				$filepath = null;
				if(isset($brochure_images[$i])){
					$filepath = $brochure_images[$i]->image_path.$brochure_images[$i]->image_filename;
					// $base64 = $this->base64_image($filepath);
				}
				$images['image'.$row] = [
					'id' => isset($brochure_images[$i]) ? $brochure_images[$i]->name : null,
					'filepath' => $filepath,
				];
			}

			$fumaco_logo = asset('storage/fumaco_logo.png');

			if(isset($request->get_images) && $request->get_images){
				return view('brochure.brochure_images', compact('images', 'current_images'));
			}

			if(isset($request->pdf) && $request->pdf){
				$new_filename = Str::slug($request->item_name, '-').'-'.Carbon::now()->format('Y-m-d');
				$project = $request->project;
				$filename = $request->filename;

				$attrib = [];
				foreach ($attributes as $att) {
					$attrib[$att->attribute] = $att->attribute_value;
					$attributes_arr[] = [
						'attribute_name' => $att->attr_name ? $att->attr_name : $att->attribute,
						'attribute_value' => $att->attribute_value
					];
				}

				DB::table('tabItem')->where('name', $request->item_code)->update([
					'item_brochure_name' => $request->item_name,
					'item_brochure_description' => $request->description,
					'modified' => Carbon::now()->toDateTimeString(),
					'modified_by' => Auth::user()->wh_user
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
					'attributes' => $attributes_arr,
					'attrib' => $attrib,
					'remarks' => $remarks
				];

				$is_standard = true;
				DB::commit();

				$content = $this->resolveBrochureImagePathsForPdf($content, $project, true);

				$pdf = Pdf::loadView('brochure.pdf', compact('content', 'project', 'filename', 'is_standard', 'remarks', 'fumaco_logo'));
				return $pdf->stream($new_filename.'.pdf');
			}

			$img_check = collect($current_images)->map(function ($q){
				return Storage::disk('public')->exists($q['filepath']) ? 1 : 0;
			})->max();

			return view('brochure.preview_standard_brochure', compact('data', 'attributes', 'images', 'current_images', 'img_check', 'remarks', 'fumaco_logo'));
		} catch (\Throwable $th) {
			DB::rollback();
			throw $th;
		}
	}

	public function uploadImageForStandard(Request $request) {
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
			$item_code = $request->item_code;
			$transaction_date = Carbon::now()->toDateTimeString();
			$transaction_started = false;
			$storedFilename = null;
			$image_path = null;
			$filename = null;

			if ($request->existing) {
				$filename = $request->selected_image;
				$base = pathinfo($filename, PATHINFO_FILENAME);
				$webpFilename = $base . '.webp';
				$image_path = 'img/';
				$storedFilename = Storage::disk('public')->exists($image_path . $webpFilename)
					? $webpFilename
					: $filename;
			}

			if ($request->hasFile('selected-file')) {
				$file = $request->file('selected-file');
				$allowed_extensions = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG', 'webp', 'WEBP'];
	
				$file_ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

				if(!in_array($file_ext, $allowed_extensions)){
					return response()->json(['status' => 0, 'message' => 'Sorry, only .jpeg, .jpg and .png files are allowed.']);
				}

				$filenamewithextension = $file->getClientOriginalName();
				$filename = pathinfo($filenamewithextension, PATHINFO_FILENAME);
				$filename = str_replace(' ', '-', $filename);
                $extension = $file->getClientOriginalExtension();

                // Paths for storage
                $image_path = 'brochures/';
                // $jpegFilename = "$filename.$extension";
                $webpFilename = "$filename.webp";

                // Save the original file
                // Storage::putFileAs($image_path, $file, $jpegFilename);

                // Create and save the WebP version
				$should_cleanup_webp = false;
				try {
					if (strtolower($file_ext) != 'webp') {
						$webp = Webp::make($file);

						if(!File::exists(public_path('temp'))){
							File::makeDirectory(public_path('temp'), 0755, true);
						}

						$webp_path = public_path("temp/$webpFilename");
						$webp->save($webp_path);
						$should_cleanup_webp = true;
					} else {
						$webp_path = $file->getPathname();
					}
		
					$web_stream = fopen($webp_path, 'r');
					Storage::disk('public')->put("$image_path$webpFilename", $web_stream);
					if (is_resource($web_stream)) {
						fclose($web_stream);
					}
					$storedFilename = $webpFilename;

					if ($should_cleanup_webp && File::exists($webp_path)) {
						unlink($webp_path);
					}
				} catch (\Throwable $e) {
					Log::warning('WebP conversion failed, saving original image', [
						'error' => $e->getMessage(),
						'original_name' => $filenamewithextension,
					]);

					$storedFilename = "$filename.$extension";
					$fallback_stream = fopen($file->getPathname(), 'r');
					Storage::disk('public')->put("$image_path$storedFilename", $fallback_stream);
					if (is_resource($fallback_stream)) {
						fclose($fallback_stream);
					}
				}
			} elseif (!$request->existing) {
				return response()->json(['status' => 0, 'message' => 'No image was provided.']);
			}

			if (!$storedFilename || !$image_path) {
				return response()->json(['status' => 0, 'message' => 'Image upload failed. Please try again.']);
			}

			DB::beginTransaction();
			$transaction_started = true;

			$existing_image_idx = DB::table('tabItem Brochure Image')->where('parent', $item_code)->where('idx', $request->image_idx)->first();
			if ($existing_image_idx) {
				DB::table('tabItem Brochure Image')->where('name', $existing_image_idx->name)->update([
					'modified' => $transaction_date,
					'modified_by' => Auth::user()->wh_user,
					'idx' => $request->image_idx,
					'image_filename' => $storedFilename
				]);
			} else {
				DB::table('tabItem Brochure Image')->insert([
					'name' => uniqid(),
					'creation' => $transaction_date,
					'modified' => $transaction_date,
					'modified_by' => Auth::user()->wh_user,
					'owner' => Auth::user()->wh_user,
					'parent' => $item_code,
					'idx' => $request->image_idx,
					'image_filename' => $storedFilename,
					'image_path' => "storage/$image_path"
				]);
			}

			DB::table('tabProduct Brochure Log')->insert([
				'name' => uniqid(),
				'creation' => $transaction_date,
				'modified' => $transaction_date,
				'modified_by' => Auth::user()->wh_user,
				'owner' => Auth::user()->wh_user,
				'project' => $project,
				'filename' => $filename,
				'created_by' => Auth::user()->wh_user,
				'transaction_date' => $transaction_date,
				'remarks' => 'For ' . $item_code,
				'transaction_type' => 'Upload Image'
			]);

			DB::commit();

			$data_src = "storage/$image_path$storedFilename";

			Log::info('uploadImageForStandard success', [
				'item_code' => $item_code,
				'image_idx' => $request->image_idx,
				'image_path' => $data_src,
			]);

			return response()->json(['status' => 1, 'message' => 'Image uploaded.', 'src' => $data_src]);
		} catch (\Throwable $e) {
			if ($transaction_started) {
				DB::rollback();
			}
			Log::error('uploadImageForStandard failed', [
				'error' => $e->getMessage(),
				'project' => $request->project,
				'item_code' => $request->item_code,
				'image_idx' => $request->image_idx,
				'existing' => (bool) $request->existing,
				'has_file' => $request->hasFile('selected-file'),
			]);
			return response()->json(['status' => 0, 'message' => 'Something went wrong. Please try again.']);
		}
	}

	public function getItemAttributes($item_code) {
		$attributes = DB::table('tabItem Variant Attribute as variant')
			->join('tabItem Attribute as attr', 'attr.name', 'variant.attribute')
			->where('variant.parent', $item_code)
			->select('variant.attribute', 'variant.attribute_value', 'attr.name', 'attr.attr_name', 'variant.hide_in_brochure')
			->orderByRaw('LENGTH(variant.brochure_idx) ASC')->orderBy('variant.brochure_idx', 'ASC')->orderBy('variant.idx')->get();

		$remarks = DB::table('tabItem')->where('name', $item_code)->pluck('item_brochure_remarks')->first();

		return view('brochure.manage_item_attributes', compact('attributes', 'item_code', 'remarks'));
	}

	public function updateBrochureAttributes(Request $request) {
		DB::beginTransaction();
		try {
			$transaction_date = Carbon::now()->toDateTimeString();
			$request_attributes = $request->attribute;
			$current_attributes = $request->current_attribute;
			$hidden_attributes = collect($request->hidden_attributes)->filter()->values()->all();
			$idx = 0;
			foreach ($request_attributes as $attribute_name => $new_attribute_name) {
				if ($current_attributes[$attribute_name] != $new_attribute_name) {
					DB::table('tabItem Attribute')->where('name', $attribute_name)->update([
						'attr_name' => $new_attribute_name,
						'modified' => $transaction_date,
						'modified_by' => Auth::user()->wh_user,
					]);
				}
			}

			foreach ($current_attributes as $name => $attribute) {
				DB::table('tabItem Variant Attribute')->where('parent', $request->item_code)->where('attribute', $attribute)->update([
					'brochure_idx' => $idx += 1,
					'hide_in_brochure' => in_array($attribute, $hidden_attributes) ? 1 : 0,
					'modified_by' => Auth::user()->wh_user,
					'modified' => Carbon::now()->toDateTimeString()
				]);
			}

			DB::table('tabItem')->where('name', $request->item_code)->update([
				'item_brochure_remarks' => $request->remarks,
				'modified' => Carbon::now()->toDateTimeString(),
				'modified_by' => Auth::user()->wh_user
			]);

			DB::commit();

			return response()->json(['status' => 1, 'message' => 'Item Attributes updated.']);
		} catch (Exception $e) {
			DB::rollback();

			return response()->json(['status' => 0, 'message' => 'Something went wrong. Please try again.']);
		}
	}

	/**
	 * Resolve brochure image paths and attach base64 data URIs so the PDF view can embed images reliably.
	 */
	private function resolveBrochureImagePathsForPdf(array $content, string $project, bool $is_standard): array
	{
		$imageExtensions = '/\.(png|jpg|jpeg|gif|webp|bmp)$/i';

		foreach ($content as $r => $row) {
			$content[$r]['image_data_uris'] = [1 => null, 2 => null, 3 => null];

			$filenames = [];
			if ($is_standard) {
				for ($i = 1; $i <= 3; $i++) {
					$fp = $row['images']['image'.$i]['filepath'] ?? null;
					$filenames[$i] = $fp ? basename($fp) : null;
				}
			} else {
				for ($i = 1; $i <= 3; $i++) {
					$val = $row['images']['image'.$i] ?? null;
					$filenames[$i] = $val ? trim((string) $val) : null;
				}
				// If Excel Image 1/2/3 columns are empty, use any attribute value that looks like an image filename
				$fromAttrs = [];
				foreach ($row['attributes'] ?? [] as $att) {
					$v = trim((string)($att['attribute_value'] ?? ''));
					if ($v && preg_match($imageExtensions, $v)) {
						$fromAttrs[] = $v;
					}
				}
				$idx = 0;
				for ($i = 1; $i <= 3; $i++) {
					if (empty($filenames[$i]) && isset($fromAttrs[$idx])) {
						$filenames[$i] = $fromAttrs[$idx];
						$idx++;
					}
				}
			}

			for ($i = 1; $i <= 3; $i++) {
				$name = $filenames[$i] ?? null;
				if (!$name) {
					continue;
				}

				$absolutePath = null;
				if ($is_standard) {
					$filepath = $row['images']['image'.$i]['filepath'] ?? null;
					if ($filepath) {
						$absolutePath = public_path($filepath);
						if (!file_exists($absolutePath)) {
							$absolutePath = storage_path('app/public/' . preg_replace('#^storage/#', '', $filepath));
						}
					}
				} else {
					$tryPaths = [
						storage_path('app/public/brochures/' . strtoupper($project) . '/' . $name),
						storage_path('app/public/brochures/' . $name),
					];
					foreach ($tryPaths as $p) {
						if ($p && file_exists($p)) {
							$absolutePath = $p;
							break;
						}
					}
				}

				if ($absolutePath && file_exists($absolutePath)) {
					$mime = @mime_content_type($absolutePath) ?: 'image/png';
					$data = @file_get_contents($absolutePath);
					if ($data !== false) {
						$content[$r]['image_data_uris'][$i] = 'data:' . $mime . ';base64,' . base64_encode($data);
					}
				}
			}
		}

		return $content;
	}
}