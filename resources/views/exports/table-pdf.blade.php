<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 8mm 6mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: {{ $fontSize }}px;
            color: #1e293b;
            margin: 0;
        }

        h1 {
            font-size: {{ max($headerFontSize + 8, 14) }}px;
            margin: 0 0 4px;
        }

        p.meta {
            margin: 0 0 10px;
            color: #64748b;
            font-size: {{ max($headerFontSize, 7) }}px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        col {
            width: {{ number_format(100 / max($columnCount, 1), 4, '.', '') }}%;
        }

        th,
        td {
            border: 1px solid #cbd5e1;
            padding: {{ $cellPadding }};
            text-align: left;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
            word-break: break-word;
            white-space: normal;
            line-height: 1.25;
            font-size: {{ $fontSize }}px;
        }

        th {
            background: #f1f5f9;
            font-size: {{ $headerFontSize }}px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        tr:nth-child(even) td {
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="meta">Generated {{ $generatedAt }} · {{ count($rows) }} records · {{ $columnCount }} columns</p>
    <table>
        <colgroup>
            @for ($i = 0; $i < $columnCount; $i++)
                <col>
            @endfor
        </colgroup>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $columnCount }}">No records found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
