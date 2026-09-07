<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * خروجی CSV سازگار با Excel (با BOM برای نمایش درست فارسی)
 */
class CsvExport
{
    /**
     * @param  array<int,string>  $headers
     * @param  iterable<array<int,mixed>>  $rows
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');

            // BOM برای نمایش صحیح فارسی در Excel
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => (string) ($v ?? ''), $row));
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
