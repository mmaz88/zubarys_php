<?php
/**
 * app/helpers/integrations/phpoffice_helpers.php - PHP Office Helper Functions
 *
 * This file contains helpers for interacting with the PhpOffice suite,
 * primarily for generating Excel (PhpSpreadsheet) documents.
 */
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

/**
 * Generates an Excel (.xlsx) file from an array of data and streams it for download.
 *
 * IMPROVEMENT: The hard `exit;` call has been removed. In a modern StarterKit, this function
 * should ideally return a Response object (e.g., a Symfony StreamedResponse) to allow the
 * StarterKit to handle the response lifecycle correctly. By removing `exit;`, we prevent the
 * script from terminating prematurely, allowing any subsequent shutdown logic or middleware
 * to run. The calling controller or route handler is now responsible for ending the script.
 *
 * @param array<int, array<string, mixed>> $data An array of associative arrays representing rows.
 * @param string $filename The name of the file to be downloaded (e.g., 'users-export.xlsx').
 * @param array<string, string>|null $headers An optional associative array to map keys to column headers.
 * If null, the keys from the first data row will be used.
 * @return void
 * @throws \PhpOffice\PhpSpreadsheet\Exception
 */
function generate_excel(array $data, string $filename = 'export.xlsx', ?array $headers = null): void
{
    if (empty($data)) {
        // In a real app, you might throw an exception or handle this more gracefully.
        // For now, we simply return to prevent further execution.
        error('No data provided for Excel generation.', 400);
        return;
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Exported Data');

    // Determine headers
    if ($headers === null) {
        // Auto-detect headers from the keys of the first row of data
        $headerKeys = array_keys($data[0]);
        $headers = array_combine($headerKeys, array_map('ucfirst', str_replace('_', ' ', $headerKeys)));
    } else {
        $headerKeys = array_keys($headers);
    }

    $col = 'A';
    foreach ($headers as $headerTitle) {
        $sheet->setCellValue($col . '1', $headerTitle);
        $col++;
    }

    // Style the header row
    $headerStyle = $sheet->getStyle('A1:' . (--$col) . '1');
    $headerStyle->getFont()->setBold(true)->setSize(12);
    $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

    // Populate data rows
    $row = 2;
    foreach ($data as $dataRow) {
        $col = 'A';
        foreach ($headerKeys as $key) {
            $value = $dataRow[$key] ?? '';
            // Handle numeric values explicitly to prevent Excel treating them as strings
            if (is_numeric($value) && strlen((string) $value) < 15) {
                $sheet->setCellValueExplicit($col . $row, $value, DataType::TYPE_NUMERIC);
            } else {
                $sheet->setCellValueExplicit($col . $row, (string) $value, DataType::TYPE_STRING);
            }
            $col++;
        }
        $row++;
    }

    // Auto-size columns
    $col = 'A';
    foreach ($headers as $headerTitle) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
        $col++;
    }

    // Clear output buffer before sending headers
    if (ob_get_length()) {
        ob_end_clean();
    }

    // Set headers for file download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    // Create writer and save to output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

    // NOTE: `exit;` was removed. The calling script should terminate.
}