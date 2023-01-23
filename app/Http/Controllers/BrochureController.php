<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use Illuminate\Support\Str;

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

				$reader = new ReaderXlsx();
				$spreadsheet = $reader->load($attached_file);
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
					$result = [];
					for ($col = 5; $col <= $highestColumnIndex; $col++) {
						$value = $sheet->getCellByColumnAndRow($col, $row)->getValue();

						$result[] = [
							'attribute_name' => $headerRowArr[$col],
							'attribute_value' => $value
						];

						if($headerRowArr[$col]){
							$attrib[$headerRowArr[$col]] = $value != '-' ? $value : null;
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
						'project' => $sheet->getCellByColumnAndRow(2, 2)->getValue(),
						'item_name' => $item_name,
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

				if($request->ajax()){ // ajax
					return view('brochure.modal_product_list', compact('content', 'project', 'customer', 'headerRowArr'));
				}

				return view('brochure.print_preview', compact('content', 'table_of_contents'));
			}
			
		} catch (Exception $e) {
           
        }
	}
}