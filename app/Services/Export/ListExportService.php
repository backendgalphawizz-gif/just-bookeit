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

        return Pdf::loadView('exports.table-pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'generatedAt' => now()->format('M d, Y h:i A'),
        ])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    protected function filename(string $basename, string $extension): string
    {
        return Str::slug($basename).'-'.now()->format('Y-m-d-His').'.'.$extension;
    }
}
