<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as WriterXlsx;
use RuntimeException;

class BrochureExcelService
{
    /**
     * Read brochure Excel file and return parsed content, table of contents, project, customer, headers.
     *
     * @param  string|object  $file  Path string or UploadedFile
     * @return array{content: array, table_of_contents: array, project: string|null, customer: string|null, headers: array}
     *
     * @throws RuntimeException when file cannot be read
     */
    public function readFile($file): array
    {
        $path = is_object($file) && method_exists($file, 'getRealPath')
            ? $file->getRealPath()
            : $file;

        $reader = new ReaderXlsx;
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

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
            $attrib['Item Name'] = $itemName;
            $fittingType = $sheet->getCell([2, $row])->getValue();
            $attrib['Fitting Type'] = $fittingType != '-' ? $fittingType : null;
            $desc = $sheet->getCell([3, $row])->getValue();
            $attrib['Description'] = $desc != '-' ? $desc : null;
            $loc = $sheet->getCell([4, $row])->getValue();
            $attrib['Location'] = $loc != '-' ? $loc : null;

            $project = $project ?: $sheet->getCell([2, 2])->getValue();
            $customer = $customer ?: $sheet->getCell([2, 3])->getValue();

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
                'attrib' => $attrib,
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

    /**
     * Find column index (1-based) for a given header value in row 4. Returns null if not found.
     */
    public function findColumnIndexByHeader(string $excelPath, string $headerValue): ?int
    {
        $reader = new ReaderXlsx;
        $spreadsheet = $reader->load($excelPath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $value = $sheet->getCell([$col, 4])->getValue();
            if ($value == $headerValue) {
                return $col;
            }
        }

        return null;
    }

    /**
     * Set cell value at given column and row (1-based), then save the spreadsheet.
     *
     * @throws RuntimeException when file does not exist or save fails
     */
    public function setCellValueAndSave(string $excelPath, int $column, int $row, $value): void
    {
        if (! file_exists($excelPath)) {
            throw new RuntimeException('Brochure file not found: '.$excelPath);
        }

        $reader = new ReaderXlsx;
        $spreadsheet = $reader->load($excelPath);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue([$column, $row], $value);

        $writer = new WriterXlsx($spreadsheet);
        $writer->save($excelPath);
    }
}
