<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Str;
use Storage;
use DB;
use Carbon\Carbon;

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

				$allowed_extensions = ['xlsx', 'xls'];

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

	public function previewBrochure($project, $filename) {
		$file = storage_path('app/public/brochures/'. $project .'/'. $filename);

		$file_contents = $this->readFile($file);

		$content = $file_contents['content'];
		$project = $file_contents['project'];
		$table_of_contents = $file_contents['table_of_contents'];

		return view('brochure.print_preview', compact('content', 'table_of_contents', 'project', 'filename'));
	}

	public function uploadImage(Request $request) {
		DB::beginTransaction();
		try {
			if($request->hasFile('selected-file')){
				// return $request->all();
				$file = $request->file('selected-file');
				$allowed_extensions = ['jpg', 'jpeg', 'png'];
	
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
		try {
			$folder = $request->project;
			$dir = $request->filename;

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
			
			$sheet->setCellValueByColumnAndRow($column, $row, null);

			$writer = new WriterXlsx($spreadsheet);
			$writer->save($excel_file);

			return response()->json(['status' => 1, 'message' => 'Image removed.']);
		}catch (Exception $e){
			return response()->json(['status' => 0, 'message' => 'Something went wrong. Please try again.']);
		}
	}
}