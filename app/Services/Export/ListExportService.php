<?php

namespace App\Services\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListExportService
{
    public function respond(
        Request $request,
        Builder $query,
        array $headers,
        Closure $mapRow,
        string $basename,
        string $title,
    ): StreamedResponse|Response {
        $format = $request->string('format', 'csv')->toString();

        if (! in_array($format, ['csv', 'pdf'], true)) {
            abort(422, 'Invalid export format.');
        }

        $rows = $query->lazy(200)->map(fn ($row) => $mapRow($row))->all();

        return $format === 'pdf'
            ? $this->pdf($title, $headers, $rows, $basename)
            : $this->csv($headers, $rows, $basename);
    }

    public function csv(array $headers, array $rows, string $basename): StreamedResponse
    {
        $filename = $this->filename($basename, 'csv');

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(string $title, array $headers, array $rows, string $basename): Response
    {
        $filename = $this->filename($basename, 'pdf');
        $layout = $this->pdfLayoutForColumns(count($headers));

        return Pdf::loadView('exports.table-pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'generatedAt' => now()->format('M d, Y h:i A'),
            'columnCount' => count($headers),
            'fontSize' => $layout['fontSize'],
            'headerFontSize' => $layout['headerFontSize'],
            'cellPadding' => $layout['cellPadding'],
        ])
            ->setPaper($layout['paper'], $layout['orientation'])
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->download($filename);
    }

    /** @return array{paper: string, orientation: string, fontSize: int, headerFontSize: int, cellPadding: string} */
    protected function pdfLayoutForColumns(int $columnCount): array
    {
        return match (true) {
            $columnCount >= 11 => [
                'paper' => 'a3',
                'orientation' => 'landscape',
                'fontSize' => 6,
                'headerFontSize' => 6,
                'cellPadding' => '2px 3px',
            ],
            $columnCount >= 9 => [
                'paper' => 'a3',
                'orientation' => 'landscape',
                'fontSize' => 7,
                'headerFontSize' => 7,
                'cellPadding' => '3px 4px',
            ],
            $columnCount >= 7 => [
                'paper' => 'a3',
                'orientation' => 'landscape',
                'fontSize' => 8,
                'headerFontSize' => 8,
                'cellPadding' => '3px 5px',
            ],
            default => [
                'paper' => 'a4',
                'orientation' => 'landscape',
                'fontSize' => 9,
                'headerFontSize' => 8,
                'cellPadding' => '4px 6px',
            ],
        };
    }

    protected function filename(string $basename, string $extension): string
    {
        return Str::slug($basename).'-'.now()->format('Y-m-d-His').'.'.$extension;
    }
}
