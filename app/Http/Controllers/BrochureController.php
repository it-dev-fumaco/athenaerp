<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Str;
use Storage;

class BrochureController extends Controller
{
    public function viewForm() {
        return view('brochure.form');
    }

    public function readExcelFile(Request $request){
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

				if($request->ajax()){
					return view('brochure.modal_product_list', compact('content', 'project', 'customer', 'headers'));
				}

				if(!Storage::disk('public')->exists('/brochures/'.strtoupper($project))){
					Storage::disk('public')->makeDirectory('/brochures/'.strtoupper($project));
				}

				$attached_file->move(public_path('storage/brochures/'.strtoupper($project)), $attached_file->getClientOriginalName());

				return redirect('/preview/' . $project . '/' . $file_name . '.' . $file_ext);
			}
		} catch (Exception $e) {
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
		if($request->hasFile('selected-file')){
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
	
			$destinationPath = storage_path('app/public/brochures/');

			$filename = $filename . '.' . $extension;

			$file->move($destinationPath, $filename);

			$excel_file = storage_path('app/public/brochures/'.$folder.'/'. $dir);
			
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

			return response()->json(['status' => 1, 'message' => 'Image uploaded.']);
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

}