<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class XlsxExport
{
    /**
     * Streaming download file .xlsx.
     *
     * @param  string    $filename  nama file (tanpa ekstensi)
     * @param  array     $headings  baris judul kolom
     * @param  iterable  $rows      array baris (tiap baris = array nilai)
     * @param  array     $footer    baris ringkasan opsional
     */
    public static function download(string $filename, array $headings, iterable $rows, array $footer = []): StreamedResponse
    {
        return new StreamedResponse(function () use ($headings, $rows, $footer) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $headStyle = (new Style())->withFontBold(true);
            $writer->addRow(Row::fromValuesWithStyle($headings, $headStyle));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues(array_values($row)));
            }

            if (! empty($footer)) {
                $writer->addRow(Row::fromValuesWithStyle($footer, $headStyle));
            }

            $writer->close();
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xlsx"',
        ]);
    }
}
