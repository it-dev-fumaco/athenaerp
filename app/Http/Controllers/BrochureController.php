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
            if($request->hasFile('file')){
				$attached_file = $request->file('file');

				$allowed_extensions = ['xlsx', 'xls'];

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

				// $worksheetInfo = $reader->listWorksheetInfo($attached_file);
				// $totalRows = $worksheetInfo[0]['totalRows'];

				$headerRowArr = [];
				for ($col = 1; $col <= $highestColumnIndex; $col++) {
					$value = $sheet->getCellByColumnAndRow($col, 4)->getValue();

					$headerRowArr[$col] = $value;
				}

				$content = $table_of_contents = [];
				for ($row = 5; $row <= $highestRow; $row++) {
					$result = [];
					for ($col = 5; $col <= $highestColumnIndex; $col++) {
						$value = $sheet->getCellByColumnAndRow($col, $row)->getValue();

						$result[] = [
							'attribute_name' => $headerRowArr[$col],
							'attribute_value' => $value
						];
					}

					$item_name = $sheet->getCellByColumnAndRow(1, $row)->getValue();

					$content[] = [
						'project' => $sheet->getCellByColumnAndRow(2, 2)->getValue(),
						'item_name' => $item_name,
						'reference' => $sheet->getCellByColumnAndRow(2, $row)->getValue(),
						'description' => $sheet->getCellByColumnAndRow(3, $row)->getValue(),
						'location' => $sheet->getCellByColumnAndRow(4, $row)->getValue(),
						'attributes' => $result,
					];

					$table_of_contents[] = [
						'id' => Str::slug($item_name, '-'),
						'text' => $item_name
					];
				}

				return view('brochure.print_preview', compact('content', 'table_of_contents'));

				// return $aMergeCells = $sheet->getMergeCells();

				return $testArr;

				dd($worksheetInfo);


				// Check cell is merged or not
				// function checkMergedCell($sheet, $cell){
				// 	foreach ($sheet->getMergeCells() as $cells) {
				// 		if ($cell->isInRange($cells)) {
				// 			// Cell is merged!
				// 			return true;
				// 		}
				// 	}
				// 	return false;
				// }
			}
			
		} catch (Exception $e) {
           
        }
	}
}