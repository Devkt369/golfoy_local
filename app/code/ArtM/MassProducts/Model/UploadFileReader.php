<?php
namespace ArtM\MassProducts\Model;

use Magento\Framework\Exception\LocalizedException;

class UploadFileReader
{
    /**
     * @param string $path
     * @param string $extension
     * @return array<int, string>
     * @throws LocalizedException
     */
    public function extractHeaderColumns(string $path, string $extension): array
    {
        if ($extension === 'csv') {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw new LocalizedException(__('Unable to read CSV file.'));
            }

            $headers = fgetcsv($handle) ?: [];
            fclose($handle);

            return $this->normalizeHeaders($headers);
        }

        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new LocalizedException(__('XLSX support is unavailable. PHPSpreadsheet is not installed.'));
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestColumn = $sheet->getHighestColumn();
        $headerRow = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false);

        return $this->normalizeHeaders($headerRow[0] ?? []);
    }

    /**
     * @param string $path
     * @param string $extension
     * @param string $columnName
     * @return array<int, string>
     * @throws LocalizedException
     */
    public function extractColumnValues(string $path, string $extension, string $columnName): array
    {
        $headers = $this->extractHeaderColumns($path, $extension);
        $targetIndex = $this->findColumnIndex($headers, $columnName);
        if ($targetIndex === null) {
            return [];
        }

        if ($extension === 'csv') {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw new LocalizedException(__('Unable to read CSV file.'));
            }

            fgetcsv($handle);
            $values = [];
            while (($row = fgetcsv($handle)) !== false) {
                $value = $this->sanitizeCell($row[$targetIndex] ?? '');
                if ($value !== '') {
                    $values[$this->normalizeKey($value)] = $value;
                }
            }
            fclose($handle);

            return array_values($values);
        }

        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new LocalizedException(__('XLSX support is unavailable. PHPSpreadsheet is not installed.'));
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = (int)$sheet->getHighestRow();
        $columnIndex = $targetIndex + 1;
        $values = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $value = $this->sanitizeCell($sheet->getCellByColumnAndRow($columnIndex, $row)->getValue());
            if ($value !== '') {
                $values[$this->normalizeKey($value)] = $value;
            }
        }

        return array_values($values);
    }

    /**
     * @param string $path
     * @param string $extension
     * @return array<int, array<string, mixed>>
     * @throws LocalizedException
     */
    public function extractRows(string $path, string $extension): array
    {
        $headers = $this->extractHeaderColumns($path, $extension);
        $rows = [];

        if ($extension === 'csv') {
            $handle = fopen($path, 'r');
            if ($handle === false) {
                throw new LocalizedException(__('Unable to read CSV file.'));
            }

            fgetcsv($handle);
            $rowNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $data = [];
                foreach ($headers as $index => $header) {
                    $data[$header] = $this->sanitizeCell($row[$index] ?? '');
                }
                $rows[] = [
                    'row_number' => $rowNumber,
                    'data' => $data,
                ];
                $rowNumber++;
            }
            fclose($handle);

            return $rows;
        }

        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new LocalizedException(__('XLSX support is unavailable. PHPSpreadsheet is not installed.'));
        }

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = (int)$sheet->getHighestRow();

        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $data = [];
            foreach ($headers as $index => $header) {
                $columnIndex = $index + 1;
                $value = $sheet->getCellByColumnAndRow($columnIndex, $rowNumber)->getValue();
                $data[$header] = $this->sanitizeCell($value);
            }
            $rows[] = [
                'row_number' => ($rowNumber - 1),
                'data' => $data,
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, mixed> $headers
     * @return array<int, string>
     */
    public function normalizeHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $value) {
            $header = $this->sanitizeCell($value);
            if ($header !== '') {
                $result[] = $header;
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function sanitizeCell($value): string
    {
        $cell = (string)$value;
        $cell = preg_replace('/^\xEF\xBB\xBF/', '', $cell);
        $cell = is_string($cell) ? $cell : '';
        $cell = preg_replace('/^\x{FEFF}/u', '', $cell);
        $cell = is_string($cell) ? $cell : '';
        if ($cell !== '' && function_exists('mb_detect_encoding') && mb_detect_encoding($cell, 'UTF-8', true) === false) {
            $converted = false;
            if (function_exists('iconv')) {
                $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $cell);
                if ($converted === false || $converted === '') {
                    $converted = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $cell);
                }
            }
            if ($converted !== false && $converted !== '') {
                $cell = $converted;
            }
        }
        $cell = trim($cell ?? '');
        $cell = preg_replace('/\s+/u', ' ', $cell);
        $cell = is_string($cell) ? $cell : '';

        return $cell;
    }

    /**
     * @param array<int, string> $headers
     * @param string $columnName
     * @return int|null
     */
    private function findColumnIndex(array $headers, string $columnName): ?int
    {
        $target = $this->normalizeKey($columnName);
        foreach ($headers as $index => $header) {
            if ($this->normalizeKey($header) === $target) {
                return $index;
            }
        }

        return null;
    }

    private function normalizeKey(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }
}
