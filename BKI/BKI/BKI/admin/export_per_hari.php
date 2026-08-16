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
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;


// =====================================================
// CEK METHOD
// =====================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Request tidak valid.");
}


// =====================================================
// CEK TANGGAL
// =====================================================

if (
    !isset($_POST['export_date']) ||
    empty($_POST['export_date'])
) {
    die("Tanggal belum dipilih.");
}

$export_date = $_POST['export_date'];


// =====================================================
// VALIDASI TANGGAL
// =====================================================

$date = DateTime::createFromFormat(
    'Y-m-d',
    $export_date
);

if (
    !$date ||
    $date->format('Y-m-d') !== $export_date
) {
    die("Format tanggal tidak valid.");
}


// =====================================================
// BUAT SPREADSHEET
// =====================================================

$spreadsheet = new Spreadsheet();

$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle(
    'Planning Export'
);


// =====================================================
// JUDUL
// =====================================================

$sheet->mergeCells('A1:J1');

$sheet->setCellValue(
    'A1',
    'Planning Export (' .
    date(
        'd-m-Y',
        strtotime($export_date)
    ) .
    ')'
);

$sheet->getStyle('A1')
    ->getFont()
    ->setBold(true)
    ->setSize(16);

$sheet->getStyle('A1')
    ->getAlignment()
    ->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    )
    ->setVertical(
        Alignment::VERTICAL_CENTER
    );

$sheet->getRowDimension(1)
    ->setRowHeight(30);


// =====================================================
// HEADER
// =====================================================

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

    $sheet->setCellValue(
        $column . '3',
        $header
    );

    $column++;
}


// =====================================================
// STYLE HEADER
// =====================================================

$headerStyle =
    $sheet->getStyle('A3:J3');

$headerStyle
    ->getFont()
    ->setBold(true);

$headerStyle
    ->getAlignment()
    ->setHorizontal(
        Alignment::HORIZONTAL_CENTER
    )
    ->setVertical(
        Alignment::VERTICAL_CENTER
    );

$headerStyle
    ->getFill()
    ->setFillType(
        Fill::FILL_SOLID
    )
    ->getStartColor()
    ->setARGB('D9EAF7');

$headerStyle
    ->getBorders()
    ->getAllBorders()
    ->setBorderStyle(
        Border::BORDER_THIN
    );

$sheet->getRowDimension(3)
    ->setRowHeight(25);


// =====================================================
// QUERY DATA BERDASARKAN TANGGAL
// =====================================================

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

    JOIN users u
        ON p.user_id = u.id

    WHERE p.tanggal = '$export_date'
      AND u.status = 'Active'

    ORDER BY
        p.status DESC,
        p.time_upload_activity_planning ASC
";


$result = mysqli_query(
    $koneksi,
    $query
);


if (!$result) {

    die(
        "Query error: " .
        mysqli_error($koneksi)
    );
}


// =====================================================
// ISI DATA
// =====================================================

$rowNumber = 4;

$no = 1;


while (
    $row = mysqli_fetch_assoc($result)
) {


    // =================================================
    // STATUS
    // =================================================

    $status = !empty($row['gambar'])
        ? 'Completed'
        : 'On-progress';


    // =================================================
    // DATA BIASA
    // =================================================

    $sheet->setCellValue(
        'A' . $rowNumber,
        $no
    );


    $sheet->setCellValue(
        'B' . $rowNumber,
        !empty($row['tanggal'])
            ? date(
                'd-m-Y',
                strtotime($row['tanggal'])
            )
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
        'I' . $rowNumber,
        $status
    );


    $sheet->setCellValue(
        'J' . $rowNumber,
        $row['history_update']
    );


    // =================================================
    // GAMBAR
    // =================================================

    if (!empty($row['gambar'])) {


        // =============================================
        // PECAH NAMA GAMBAR
        // =============================================

        $gambarPaths = explode(
            ',',
            $row['gambar']
        );


        $imageIndex = 0;


        // =============================================
        // LOOP SEMUA GAMBAR
        // =============================================

        foreach (
            $gambarPaths as $path
        ) {


            $path = trim($path);


            if ($path === '') {
                continue;
            }


            // =========================================
            // LOKASI FILE GAMBAR
            // =========================================

            $imagePath =
                __DIR__ .
                '/img/' .
                $path;


            // =========================================
            // CEK FILE
            // =========================================

            if (!file_exists($imagePath)) {
                continue;
            }


            // =========================================
            // CEK UKURAN GAMBAR
            // =========================================

            $imageInfo =
                getimagesize(
                    $imagePath
                );


            if (
                $imageInfo === false ||
                empty($imageInfo[0]) ||
                empty($imageInfo[1])
            ) {
                continue;
            }


            // =========================================
            // UKURAN ASLI
            // =========================================

            $originalWidth =
                $imageInfo[0];

            $originalHeight =
                $imageInfo[1];


            // =========================================
            // TINGGI GAMBAR
            // =========================================

            $imageHeight = 100;


            // =========================================
            // HITUNG LEBAR BERDASARKAN RASIO ASLI
            // =========================================

            $imageWidth =
                (
                    $originalWidth /
                    $originalHeight
                ) *
                $imageHeight;


            // =========================================
            // BUAT DRAWING
            // =========================================

            $drawing =
                new Drawing();


            $drawing->setName(
                'Evidence_' .
                $no .
                '_' .
                $imageIndex
            );


            $drawing->setDescription(
                'Planning Evidence'
            );


            // =========================================
            // PATH GAMBAR
            // =========================================

            $drawing->setPath(
                $imagePath
            );


            // =========================================
            // CELL GAMBAR
            // =========================================

            $drawing->setCoordinates(
                'H' . $rowNumber
            );


            // =========================================
            // UKURAN GAMBAR
            // =========================================

            $drawing->setHeight(
                $imageHeight
            );


            // =========================================
            // POSISI HORIZONTAL
            // =========================================
            //
            // Kolom H = width 30
            // Perkiraan lebar = 210 px
            //
            // Foto dibuat berada di tengah.
            //

            $columnWidthPixels = 210;


            $offsetX =
                (
                    $columnWidthPixels -
                    $imageWidth
                ) / 2;


            // Jangan terlalu dekat dengan border kiri

            if ($offsetX < 8) {

                $offsetX = 8;
            }


            $drawing->setOffsetX(
                $offsetX
            );


            // =========================================
            // POSISI VERTIKAL
            // =========================================
            //
            // Foto pertama:
            // 8 px dari atas
            //
            // Foto kedua:
            // 8 + 110 px
            //
            // Foto ketiga:
            // 8 + 220 px
            //
            // Jadi ada GAP 10 px antar foto.
            //

            $imageGap = 10;


            $drawing->setOffsetY(
                8 +
                (
                    $imageIndex *
                    (
                        $imageHeight +
                        $imageGap
                    )
                )
            );


            // =========================================
            // MASUKKAN GAMBAR
            // =========================================

            $drawing->setWorksheet(
                $sheet
            );


            $imageIndex++;
        }


        // =================================================
        // TINGGI ROW
        // =================================================

        if ($imageIndex > 0) {


            /*
             * Tinggi foto = 100 px
             *
             * Gap antar foto = 10 px
             *
             * Offset atas = 8 px
             *
             * Sedikit ruang bawah = 5 px
             *
             * 1 px ≈ 0.75 point
             *
             * Rumus:
             *
             * 8 px
             * + jumlah foto × 100 px
             * + gap antar foto
             * + 5 px
             *
             * kemudian dikonversi ke point.
             */


            $totalHeightPixels =
                8 +
                (
                    $imageIndex *
                    $imageHeight
                ) +
                (
                    (
                        $imageIndex - 1
                    ) *
                    $imageGap
                ) +
                5;


            $rowHeight =
                $totalHeightPixels *
                0.75;


            // =========================================
            // MINIMAL TINGGI ROW
            // =========================================

            if ($rowHeight < 86.5) {

                $rowHeight = 86.5;
            }


            $sheet
                ->getRowDimension(
                    $rowNumber
                )
                ->setRowHeight(
                    $rowHeight
                );
        }


    } else {


        // =================================================
        // TIDAK ADA GAMBAR
        // =================================================

        $sheet->setCellValue(
            'H' . $rowNumber,
            'No Image'
        );


        $sheet
            ->getStyle(
                'H' . $rowNumber
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );


        $sheet
            ->getRowDimension(
                $rowNumber
            )
            ->setRowHeight(25);
    }


    // =================================================
    // NEXT ROW
    // =================================================

    $rowNumber++;

    $no++;
}


// =====================================================
// LAST ROW
// =====================================================

$lastRow =
    $rowNumber - 1;


// =====================================================
// BORDER DATA
// =====================================================

if ($lastRow >= 3) {


    $dataStyle =
        $sheet->getStyle(
            'A3:J' . $lastRow
        );


    $dataStyle
        ->getBorders()
        ->getAllBorders()
        ->setBorderStyle(
            Border::BORDER_THIN
        );


    $dataStyle
        ->getAlignment()
        ->setVertical(
            Alignment::VERTICAL_TOP
        );
}


// =====================================================
// ALIGNMENT
// =====================================================

foreach (
    [
        'A',
        'B',
        'C',
        'G',
        'H',
        'I'
    ]
    as $col
) {


    if ($lastRow >= 4) {


        $sheet
            ->getStyle(
                $col .
                '4:' .
                $col .
                $lastRow
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );
    }
}


// =====================================================
// VERTICAL CENTER KOLOM GAMBAR
// =====================================================

if ($lastRow >= 4) {


    $sheet
        ->getStyle(
            'H4:H' .
            $lastRow
        )
        ->getAlignment()
        ->setHorizontal(
            Alignment::HORIZONTAL_CENTER
        )
        ->setVertical(
            Alignment::VERTICAL_CENTER
        );
}


// =====================================================
// TEXT WRAP
// =====================================================

foreach (
    [
        'F',
        'J'
    ]
    as $col
) {


    if ($lastRow >= 4) {


        $sheet
            ->getStyle(
                $col .
                '4:' .
                $col .
                $lastRow
            )
            ->getAlignment()
            ->setWrapText(true);
    }
}


// =====================================================
// LEBAR KOLOM
// =====================================================

$sheet
    ->getColumnDimension('A')
    ->setWidth(5);


$sheet
    ->getColumnDimension('B')
    ->setWidth(12);


$sheet
    ->getColumnDimension('C')
    ->setWidth(12);


$sheet
    ->getColumnDimension('D')
    ->setWidth(25);


$sheet
    ->getColumnDimension('E')
    ->setWidth(15);


$sheet
    ->getColumnDimension('F')
    ->setWidth(40);


$sheet
    ->getColumnDimension('G')
    ->setWidth(15);


// =====================================================
// KOLOM GAMBAR
// =====================================================

$sheet
    ->getColumnDimension('H')
    ->setWidth(30);


$sheet
    ->getColumnDimension('I')
    ->setWidth(15);


$sheet
    ->getColumnDimension('J')
    ->setWidth(1);


// =====================================================
// FREEZE HEADER
// =====================================================

$sheet->freezePane(
    'A4'
);


// =====================================================
// FILTER
// =====================================================

if ($lastRow >= 3) {

    $sheet->setAutoFilter(
        'A3:J' . $lastRow
    );
}


// =====================================================
// DOWNLOAD XLSX
// =====================================================

$filename =
    'planning_' .
    $export_date .
    '.xlsx';


header(
    'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
);


header(
    'Content-Disposition: attachment; filename="' .
    $filename .
    '"'
);


header(
    'Cache-Control: max-age=0'
);


// =====================================================
// SAVE
// =====================================================

$writer =
    new Xlsx(
        $spreadsheet
    );


$writer->save(
    'php://output'
);


exit;

?>