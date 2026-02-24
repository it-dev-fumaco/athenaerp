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
        $maxCol = max($highestColumnIndex, 15);

        $headerRowArr = [];
        for ($col = 1; $col <= $maxCol; $col++) {
            $value = $sheet->getCell([$col, 4])->getValue();
            $headerRowArr[$col] = $value;
        }

        $content = $tableOfContents = $tblModal = $attrib = [];
        $project = $customer = null;

        for ($row = 5; $row <= $highestRow; $row++) {
            $result = $images = [];
            for ($col = 5; $col <= $maxCol; $col++) {
                $value = $sheet->getCell([$col, $row])->getValue();
                if (Arr::has($headerRowArr, $col)) {
                    $headerVal = $headerRowArr[$col];
                    $result[] = [
                        'attribute_name' => $headerVal,
                        'attribute_value' => $value,
                    ];
                    $attrib[$headerVal] = $value != '-' ? $value : null;

                    $headerNorm = $headerVal !== null && $headerVal !== ''
                        ? str_replace(' ', '', strtolower(trim((string) $headerVal)))
                        : '';
                    if ($headerNorm === 'image1') {
                        $images['image1'] = $value !== null && $value !== '' ? trim((string) $value) : null;
                    }
                    if ($headerNorm === 'image2') {
                        $images['image2'] = $value !== null && $value !== '' ? trim((string) $value) : null;
                    }
                    if ($headerNorm === 'image3') {
                        $images['image3'] = $value !== null && $value !== '' ? trim((string) $value) : null;
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
     * Find column index (1-based) for a given header value. Looks in row 4 first, then rows 1–5.
     * Comparison is trim and case-insensitive; also matches "Image1" to "Image 1".
     * Searches at least columns 1–15 so "Image 1/2/3" are found even if those columns have no data yet.
     */
    public function findColumnIndexByHeader(string $excelPath, string $headerValue): ?int
    {
        $reader = new ReaderXlsx;
        $spreadsheet = $reader->load($excelPath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $maxCol = max($highestColumnIndex, 15);

        $headerNormalized = strtolower(trim($headerValue));
        $headerNoSpace = str_replace(' ', '', $headerNormalized);

        $headerRows = [4, 3, 5, 2, 1];
        foreach ($headerRows as $row) {
            $found = $this->matchHeaderInRow($sheet, $row, $maxCol, $headerNormalized, $headerNoSpace);
            if ($found !== null) {
                return $found;
            }
        }

        if (preg_match('/^image\s*(\d)$/i', $headerNoSpace, $m) || preg_match('/^image\s*(\d)$/i', $headerNormalized, $m)) {
            $num = $m[1];
            foreach ($headerRows as $row) {
                for ($col = 1; $col <= $maxCol; $col++) {
                    $value = $sheet->getCell([$col, $row])->getValue();
                    if ($value === null || $value === '') {
                        continue;
                    }
                    $cellNorm = str_replace(' ', '', strtolower(trim((string) $value)));
                    if ($cellNorm === 'image'.$num) {
                        return $col;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Ensure "Image 1", "Image 2", "Image 3" columns exist in row 4. Adds any missing columns at the end.
     */
    public function ensureImageColumnsExist(string $excelPath): void
    {
        if (! file_exists($excelPath)) {
            return;
        }

        $reader = new ReaderXlsx;
        $spreadsheet = $reader->load($excelPath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $maxCol = max($highestColumnIndex, 4);
        $headerRow = 4;

        $existing = [];
        for ($col = 1; $col <= $maxCol; $col++) {
            $value = $sheet->getCell([$col, $headerRow])->getValue();
            if ($value !== null && $value !== '') {
                $existing[] = str_replace(' ', '', strtolower(trim((string) $value)));
            }
        }

        $required = [
            'Image 1' => 'image1',
            'Image 2' => 'image2',
            'Image 3' => 'image3',
        ];
        $toAdd = [];
        foreach ($required as $headerLabel => $normalized) {
            if (! in_array($normalized, $existing, true)) {
                $toAdd[] = $headerLabel;
            }
        }

        if (empty($toAdd)) {
            return;
        }

        $nextCol = $maxCol + 1;
        foreach ($toAdd as $headerLabel) {
            $sheet->setCellValue([$nextCol, $headerRow], $headerLabel);
            $nextCol++;
        }

        $writer = new WriterXlsx($spreadsheet);
        $writer->save($excelPath);
    }

    /**
     * Match header in a specific row; return column index or null.
     */
    private function matchHeaderInRow($sheet, int $row, int $maxCol, string $headerNormalized, string $headerNoSpace): ?int
    {
        for ($col = 1; $col <= $maxCol; $col++) {
            $value = $sheet->getCell([$col, $row])->getValue();
            if ($value === null || $value === '') {
                continue;
            }
            $cellStr = is_object($value) && method_exists($value, '__toString')
                ? (string) $value
                : (string) $value;
            $cellNormalized = strtolower(trim($cellStr));
            $cellNoSpace = str_replace(' ', '', $cellNormalized);
            if ($cellNormalized === $headerNormalized || $cellNoSpace === $headerNoSpace) {
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
