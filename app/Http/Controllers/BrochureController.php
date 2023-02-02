<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Str;
use Storage;
use Auth;
use DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class BrochureController extends Controller
{
    public function viewForm() {
		$recents = DB::table('tabProduct Brochure Log')
			->where('transaction_type', 'Upload Excel File')
			->orderBy('creation', 'desc')->limit(10)->get();

		$recents = $recents->groupby('project');

		$recent_uploads = [];
		foreach ($recents as $project => $row) {

			$seconds = Carbon::now()->diffInSeconds(Carbon::parse($row[0]->transaction_date));
			$minutes = Carbon::now()->diffInMinutes(Carbon::parse($row[0]->transaction_date));
			$hours = Carbon::now()->diffInHours(Carbon::parse($row[0]->transaction_date));
			$days = Carbon::now()->diffInDays(Carbon::parse($row[0]->transaction_date));
			$months = Carbon::now()->diffInMonths(Carbon::parse($row[0]->transaction_date));
			$years = Carbon::now()->diffInYears(Carbon::parse($row[0]->transaction_date));

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
				'project' => $project,
				'filename' => $row[0]->filename,
				'created_by' => $row[0]->created_by,
				'duration' => $duration,
			];
		}

        return view('brochure.form', compact('recent_uploads'));
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
				$project = $file_contents['project'];
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
		$file = storage_path('app/public/brochures/'. $project .'/'. $filename);

		if(!Storage::disk('public')->exists('/brochures/'. $project .'/'. $filename)){
			return redirect('brochure')->with('error', 'File '.$filename.' does not exist.');
		}

		$file_contents = $this->readFile($file);

		$content = collect($file_contents['content'])->map(function ($q){
			if($q['id'] && collect($q['attributes'])->pluck('attribute_value')->filter()->values()->all()){
				return $q;
			}
		})->filter()->values()->all();
		$project = $file_contents['project'];
		$table_of_contents = $file_contents['table_of_contents'];

		if(isset($request->pdf) && $request->pdf){
			$storage = Storage::disk('public')->files('/brochures/'.strtoupper($project));

			$series = null;
			if($storage){
				$series = count($storage) > 1 ? count($storage) : 1;
				$series = '-'.(string)$series;
			}

			$new_filename = Str::slug($project, '-').'-'.Carbon::now()->format('Y-m-d').$series;

			$pdf = Pdf::loadView('brochure.pdf', compact('content', 'project', 'filename'));
			return $pdf->stream($new_filename.'.pdf');
		}

		return view('brochure.print_preview', compact('content', 'table_of_contents', 'project', 'filename'));
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

				DB::commit();

				$data = [
					'src' => $filename,
					'item_image_id' => $request->item_image_id
				];
	
				return response()->json(['status' => 1, 'message' => 'Image uploaded.', 'data' => $data]);
			}
		} catch (Exception $e) {
			DB::rollback();

			return response()->json(['status' => 0, 'message' => 'Something went wrong. Please try again.']);
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

		$highestColumnIndex = Coordinate::columnIndexFromString($highestColumn); // e.g. 5

		$headerRowArr = [];
		for ($col = 1; $col <= $highestColumnIndex; $col++) {
			$value = $sheet->getCellByColumnAndRow($col, 4)->getValue();

			$headerRowArr[$col] = $value;
		}

		$content = $table_of_contents = $tbl_modal = [];
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

					if ($headerRowArr[$col] == 'Image 1') {
						$images['image1'] = $sheet->getCellByColumnAndRow($col, $row)->getValue();
					}
					if ($headerRowArr[$col] == 'Image 2') {
						$images['image2'] = $sheet->getCellByColumnAndRow($col, $row)->getValue();
					}
					if ($headerRowArr[$col] == 'Image 3') {
						$images['image3'] = $sheet->getCellByColumnAndRow($col, $row)->getValue();
					}
				}
			}

			$item_name = $sheet->getCellByColumnAndRow(1, $row)->getValue();

			// for ajax table
			$attrib['Item Name'] = $item_name;
			$attrib['Fitting Type'] = $sheet->getCellByColumnAndRow(2, $row)->getValue() != '-' ? $sheet->getCellByColumnAndRow(2, $row)->getValue() : null;
			$attrib['Description'] = $sheet->getCellByColumnAndRow(3, $row)->getValue() != '-' ? $sheet->getCellByColumnAndRow(3, $row)->getValue() : null;
			$attrib['Location'] = $sheet->getCellByColumnAndRow(4, $row)->getValue() != '-' ? $sheet->getCellByColumnAndRow(4, $row)->getValue() : null;
			// for ajax table

			$project = !$project ? $sheet->getCellByColumnAndRow(2, 2)->getValue() : $project;
			$customer = !$customer ? $sheet->getCellByColumnAndRow(2, 3)->getValue() : $customer;

			$content[] = [
				'id' => Str::slug($item_name, '-'),
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

	public function removeImage(Request $request) {
		DB::beginTransaction();
		try {
			if ($request->id) {
				DB::table('tabItem Brochure Image')->where('name', $request->id)->delete();

				DB::commit();

				return response()->json(['status' => 1, 'message' => 'Image removed.']);
			}

			$folder = $request->project;
			$dir = $request->filename;
			$loc = $folder && $dir ? strtoupper($folder).'/'.$dir : null;

			if($loc && Storage::disk('public')->exists('brochures/'.$loc)){
				$excel_file = storage_path('/app/public/brochures/'.$loc);
						
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
				
				$sheet->setCellValueByColumnAndRow($column, $row, null);

				$writer = new WriterXlsx($spreadsheet);
				$writer->save($excel_file);

				DB::commit();
			}

			return response()->json(['status' => 1, 'message' => 'Image removed.']);
		}catch (Exception $e){
			DB::rollback();

			return response()->json(['status' => 0, 'message' => 'Something went wrong. Please try again.']);
		}
	}

	public function generateBrochure(Request $request) {
		$data = $request->all();

		$attributes = DB::table('tabItem Variant Attribute as variant')
			->join('tabItem Attribute as attr', 'attr.name', 'variant.attribute')
			->where('variant.parent', $data['item_code'])
			->select('variant.attribute', 'variant.attribute_value', 'attr.name', 'attr.attr_name')
			->orderByRaw('LENGTH(variant.brochure_idx)', 'ASC')->orderBy('variant.brochure_idx', 'ASC')->orderBy('variant.idx')->get();

		$current_item_images = DB::table('tabItem Images')->where('parent', $data['item_code'])->get();
		$current_images = [];
		foreach ($current_item_images as $e) {
			$filename = $e->image_path;
			if(!Storage::disk('public')->exists('img/' . $filename) && $filename){
				$filename = explode(".", $filename)[0] . '.webp';
			}

			$current_images[] = [
				'filename' => $filename,
				'filepath' => 'img/' . $filename
			];	
		}

		$brochure_images = DB::table('tabItem Brochure Image')->where('parent', $data['item_code'])
			->select('image_filename', 'idx', 'image_path', 'name')->get();
		$brochure_images = collect($brochure_images)->groupBy('idx')->toArray();

		$images['image1'] = [
			'id' => isset($brochure_images[1]) ? $brochure_images[1][0]->name : null,
			'filepath' => isset($brochure_images[1]) ? $brochure_images[1][0]->image_path . $brochure_images[1][0]->image_filename : null,
		];

		$images['image2'] = [
			'id' => isset($brochure_images[2]) ? $brochure_images[2][0]->name : null,
			'filepath' => isset($brochure_images[2]) ? $brochure_images[2][0]->image_path . $brochure_images[2][0]->image_filename : null,
		];

		$images['image3'] = [
			'id' => isset($brochure_images[3]) ? $brochure_images[3][0]->name : null,
			'filepath' => isset($brochure_images[3]) ? $brochure_images[3][0]->image_path . $brochure_images[3][0]->image_filename : null,
		];

		if(!Storage::disk('public')->exists(Str::replace('storage', '', $images['image1']['filepath'])) && $images['image1']['filepath']){
			$images['image1']['filepath'] = explode(".", $images['image1']['filepath'])[0] . '.webp';
		}

		if(!Storage::disk('public')->exists(Str::replace('storage', '', $images['image2']['filepath'])) && $images['image2']['filepath']){
			$images['image2']['filepath'] = explode(".", $images['image2']['filepath'])[0] . '.webp';
		}

		if(!Storage::disk('public')->exists(Str::replace('storage', '', $images['image3']['filepath'])) && $images['image3']['filepath']){
			$images['image3']['filepath'] = explode(".", $images['image3']['filepath'])[0] . '.webp';
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

			$content[0] = [
				'id' => Str::slug($request->item_name, '-'),
				'row' => 1,
				'project' => $request->project,
				'item_name' => $request->item_name,
				'images' => $images,
				'reference' => $request->reference,
				'description' => $request->description,
				'location' => $request->location,
				'attributes' => $attributes_arr,
				'attrib' => $attrib
			];

			$is_standard = true;

			$pdf = Pdf::loadView('brochure.pdf', compact('content', 'project', 'filename', 'is_standard'));
			return $pdf->stream($new_filename.'.pdf');
		}

		$img_check = collect($current_images)->map(function ($q){
			return Storage::disk('public')->exists($q['filepath']) ? 1 : 0;
		})->max();

		return view('brochure.preview_standard_brochure', compact('data', 'attributes', 'images', 'current_images', 'img_check'));
	}

	public function uploadImageForStandard(Request $request) {
		DB::beginTransaction();
		try {
			$project = $request->project;
			$item_code = $request->item_code;
			$transaction_date = Carbon::now()->toDateTimeString();
			if ($request->existing) {
				$filename = $request->selected_image;
				$image_path = 'storage/img/';
			}

			if($request->hasFile('selected-file')){
				$file = $request->file('selected-file');
				$allowed_extensions = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG', 'webp', 'WEBP'];
	
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
	
				$filename = $item_code . '-' . $micro_time . '.' . $extension;
				$image_path = 'storage/brochures/';
			}

			$existing_image_idx = DB::table('tabItem Brochure Image')->where('parent', $item_code)->where('idx', $request->image_idx)->first();
			if ($existing_image_idx) {
				DB::table('tabItem Brochure Image')->where('name', $existing_image_idx->name)->update([
					'modified' => $transaction_date,
					'modified_by' => Auth::user()->wh_user,
					'idx' => $request->image_idx,
					'image_filename' => $filename
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
					'image_filename' => $filename,
					'image_path' => $image_path
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

			if($request->hasFile('selected-file')){
				$file->move($destinationPath, $filename);
			}

			$data_src = $image_path . $filename;

			return response()->json(['status' => 1, 'message' => 'Image uploaded.', 'src' => $data_src]);
		} catch (Exception $e) {
			DB::rollback();

			return response()->json(['status' => 0, 'message' => 'Something went wrong. Please try again.']);
		}
	}

	public function getItemAttributes($item_code) {
		$attributes = DB::table('tabItem Variant Attribute as variant')
			->join('tabItem Attribute as attr', 'attr.name', 'variant.attribute')
			->where('variant.parent', $item_code)
			->select('variant.attribute', 'variant.attribute_value', 'attr.name', 'attr.attr_name')
			->orderByRaw('LENGTH(variant.brochure_idx)', 'ASC')->orderBy('variant.brochure_idx', 'ASC')->orderBy('variant.idx')->get();

		return view('brochure.manage_item_attributes', compact('attributes', 'item_code'));
	}

	public function updateBrochureAttributes(Request $request) {
		DB::beginTransaction();
		try {
			$transaction_date = Carbon::now()->toDateTimeString();
			$request_attributes = $request->attribute;
			$current_attributes = $request->current_attribute;
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
					'modified_by' => Auth::user()->wh_user,
					'modified' => Carbon::now()->toDateTimeString()
				]);
			}

			DB::commit();

			return response()->json(['status' => 1, 'message' => 'Item Attributes updated.']);
		} catch (Exception $e) {
			DB::rollback();

			return response()->json(['status' => 0, 'message' => 'Something went wrong. Please try again.']);
		}
	}
}