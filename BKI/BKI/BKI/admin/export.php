<?php

session_start();

if (!isset($_SESSION['username'])) {
    header("Location: Halaman_login.php");
    exit;
}

require __DIR__ . '/koneksi.php';
require __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// ==========================
// BUAT SPREADSHEET
// ==========================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Planning Export');

// ==========================
// JUDUL
// ==========================
$sheet->mergeCells('A1:J1');
$sheet->setCellValue('A1', 'Planning Export');

$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(
    Alignment::HORIZONTAL_CENTER
);
$sheet->getStyle('A1')->getAlignment()->setVertical(
    Alignment::VERTICAL_CENTER
);

$sheet->getRowDimension(1)->setRowHeight(30);

// ==========================
// HEADER
// ==========================
$headers = [
    'No.',
    'Date',
    'NUP',
    'Name',
    'Division',
    'Description',
    'Upload Time',
    'Image',
    'Status',
    'History'
];

$column = 'A';

foreach ($headers as $header) {
    $sheet->setCellValue($column . '3', $header);
    $column++;
}

// Style Header
$headerStyle = $sheet->getStyle('A3:J3');

$headerStyle->getFont()->setBold(true);

$headerStyle->getAlignment()->setHorizontal(
    Alignment::HORIZONTAL_CENTER
);

$headerStyle->getAlignment()->setVertical(
    Alignment::VERTICAL_CENTER
);

$headerStyle->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()
    ->setARGB('D9EAF7');

$headerStyle->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

// ==========================
// QUERY DATA
// ==========================
$query = "
    SELECT 
        p.id,
        p.tanggal,
        p.deskripsi,
        p.time_upload_activity_planning,
        p.status,
        p.gambar,
        p.history_update,
        u.nup,
        u.nama,
        u.divisi
    FROM planning p
    JOIN users u ON p.user_id = u.id
    WHERE u.status = 'Active'
    ORDER BY 
        p.status DESC,
        p.time_upload_activity_planning ASC
";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("Query error: " . mysqli_error($koneksi));
}

// ==========================
// ISI DATA
// ==========================
$rowNumber = 4;
$no = 1;

while ($row = mysqli_fetch_assoc($result)) {

    // Status berdasarkan ada/tidaknya gambar
    $status = !empty($row['gambar'])
        ? 'Completed'
        : 'On-progress';

    // Gambar
    $gambar = '';

    if (!empty($row['gambar'])) {
        $gambarPaths = explode(',', $row['gambar']);

        $namaGambar = [];

        foreach ($gambarPaths as $path) {
            $path = trim($path);

            if (!empty($path)) {
                $namaGambar[] = $path;
            }
        }

        $gambar = implode(', ', $namaGambar);
    }

    // ==========================
    // SET CELL
    // ==========================
    $sheet->setCellValue('A' . $rowNumber, $no);

    $sheet->setCellValue(
        'B' . $rowNumber,
        !empty($row['tanggal'])
            ? date('d-m-Y', strtotime($row['tanggal']))
            : ''
    );

    $sheet->setCellValue(
        'C' . $rowNumber,
        $row['nup']
    );

    $sheet->setCellValue(
        'D' . $rowNumber,
        $row['nama']
    );

    $sheet->setCellValue(
        'E' . $rowNumber,
        $row['divisi']
    );

    $sheet->setCellValue(
        'F' . $rowNumber,
        $row['deskripsi']
    );

    $sheet->setCellValue(
        'G' . $rowNumber,
        $row['time_upload_activity_planning']
    );

    $sheet->setCellValue(
        'H' . $rowNumber,
        $gambar
    );

    $sheet->setCellValue(
        'I' . $rowNumber,
        $status
    );

    $sheet->setCellValue(
        'J' . $rowNumber,
        $row['history_update']
    );

    $rowNumber++;
    $no++;
}

// ==========================
// STYLE DATA
// ==========================
$lastRow = $rowNumber - 1;

$dataStyle = $sheet->getStyle(
    'A3:J' . $lastRow
);

$dataStyle->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_THIN);

$dataStyle->getAlignment()->setVertical(
    Alignment::VERTICAL_TOP
);

// Kolom tertentu rata tengah
$sheet->getStyle('A4:A' . $lastRow)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('B4:B' . $lastRow)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('C4:C' . $lastRow)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('G4:G' . $lastRow)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->getStyle('I4:I' . $lastRow)
    ->getAlignment()
    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Text wrap
$sheet->getStyle('F4:F' . $lastRow)
    ->getAlignment()
    ->setWrapText(true);

$sheet->getStyle('H4:H' . $lastRow)
    ->getAlignment()
    ->setWrapText(true);

$sheet->getStyle('J4:J' . $lastRow)
    ->getAlignment()
    ->setWrapText(true);

// ==========================
// LEBAR KOLOM
// ==========================
$sheet->getColumnDimension('A')->setWidth(7);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(25);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(40);
$sheet->getColumnDimension('G')->setWidth(22);
$sheet->getColumnDimension('H')->setWidth(35);
$sheet->getColumnDimension('I')->setWidth(15);
$sheet->getColumnDimension('J')->setWidth(35);

// ==========================
// FREEZE HEADER
// ==========================
$sheet->freezePane('A4');

// ==========================
// FILTER
// ==========================
$sheet->setAutoFilter('A3:J' . $lastRow);

// ==========================
// DOWNLOAD XLSX
// ==========================
$filename = 'planning_data_' . date('Y-m-d_H-i-s') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit;
?>