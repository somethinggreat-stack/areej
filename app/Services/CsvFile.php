<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV in and out, shaped for Excel.
 *
 * The client works in Excel, not in a CSV-aware tool, so both directions allow
 * for what Excel actually does: it needs a BOM to read UTF-8 (without it Urdu
 * names arrive as mojibake), it writes one back when saved as "CSV UTF-8", and
 * a plain "CSV" save comes out in Windows-1252 instead.
 */
class CsvFile
{
    private const BOM = "\xEF\xBB\xBF";

    /**
     * Stream a download. Streamed rather than built in memory so a long date
     * range cannot exhaust the process.
     *
     * @param  list<string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'wb');

            fwrite($out, self::BOM);
            fputcsv($out, $headers);

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn (mixed $cell): mixed => $this->guard($cell), $row));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * The same sheet as a string, for putting inside a backup zip.
     *
     * @param  list<string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public function toString(array $headers, iterable $rows): string
    {
        $out = fopen('php://temp', 'w+b');

        fwrite($out, self::BOM);
        fputcsv($out, $headers);

        foreach ($rows as $row) {
            fputcsv($out, array_map(fn (mixed $cell): mixed => $this->guard($cell), $row));
        }

        rewind($out);
        $contents = (string) stream_get_contents($out);
        fclose($out);

        return $contents;
    }

    /**
     * Read an uploaded sheet into rows keyed by their normalised header, with
     * the spreadsheet row number (header is row 1) as the key so a skipped row
     * can be pointed at exactly.
     *
     * @return array{headers: list<string>, rows: array<int, array<string, string>>}
     */
    public function read(UploadedFile $file): array
    {
        return $this->readString((string) file_get_contents($file->getRealPath()));
    }

    /**
     * @return array{headers: list<string>, rows: array<int, array<string, string>>}
     */
    public function readString(string $contents): array
    {
        if (str_starts_with($contents, self::BOM)) {
            $contents = substr($contents, strlen(self::BOM));
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        $handle = fopen('php://temp', 'w+b');
        fwrite($handle, $contents);
        rewind($handle);

        $firstLine = strtok($contents, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $headers = [];
        $rows = [];
        $line = 0;

        while (($cells = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $line++;

            if ($headers === []) {
                $headers = array_map(fn (?string $h): string => self::normalise((string) $h), $cells);

                continue;
            }

            if (trim(implode('', array_map('strval', $cells))) === '') {
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = $this->unguard(trim((string) ($cells[$index] ?? '')));
                }
            }

            $rows[$line] = $row;
        }

        fclose($handle);

        return ['headers' => array_values(array_filter($headers)), 'rows' => $rows];
    }

    /**
     * Headers are matched case- and spacing-insensitively.
     */
    public static function normalise(string $header): string
    {
        return mb_strtolower((string) preg_replace('/\s+/u', ' ', trim($header)));
    }

    /**
     * A cell starting with = + @ (or - followed by text) is run as a formula
     * when Excel opens the file. A leading apostrophe keeps it as text.
     */
    private function guard(mixed $cell): mixed
    {
        if (! is_string($cell) || $cell === '' || is_numeric($cell)) {
            return $cell;
        }

        return in_array($cell[0], ['=', '+', '-', '@'], true) ? "'".$cell : $cell;
    }

    private function unguard(string $cell): string
    {
        return preg_match("/^'[=+\-@]/", $cell) === 1 ? substr($cell, 1) : $cell;
    }
}
