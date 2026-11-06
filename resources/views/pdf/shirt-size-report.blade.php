<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shirt Size Report {{ $report['year'] }}</title>
    <style>
        @page { margin: 24px 26px; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            line-height: 1.3;
        }

        .header {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: collapse;
        }

        .header td { vertical-align: middle; }
        .logo { width: 58px; }
        .logo img { width: 48px; height: 48px; }
        .agency { color: #64748b; font-size: 8px; letter-spacing: .45px; text-transform: uppercase; }
        h1 { margin: 2px 0; color: #111827; font-size: 17px; }
        .event { color: #1d4ed8; font-size: 9px; font-weight: bold; }
        .meta { color: #64748b; font-size: 7px; text-align: right; }

        .summary {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: separate;
            border-spacing: 6px 0;
        }

        .summary td {
            border: 1px solid #bfdbfe;
            border-radius: 5px;
            padding: 7px 9px;
            background: #eff6ff;
        }

        .summary-label { color: #64748b; font-size: 7px; text-transform: uppercase; }
        .summary-value { margin-top: 2px; color: #1e3a8a; font-size: 13px; font-weight: bold; }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .report-table thead { display: table-header-group; }
        .report-table tr { page-break-inside: avoid; }

        .report-table th,
        .report-table td {
            border: 1px solid #dbe2ea;
            padding: 4px 3px;
            text-align: center;
            vertical-align: middle;
        }

        .report-table thead th {
            color: #ffffff;
            background: #1d4ed8;
            font-size: 6.8px;
            text-transform: uppercase;
        }

        .category { width: 110px; text-align: left !important; font-weight: bold; }
        .subcategory { width: 135px; text-align: left !important; }
        .distance { width: 72px; text-align: left !important; font-weight: bold; }
        .size { width: 31px; }
        .total { width: 38px; font-weight: bold; }

        .distance-row:nth-child(even) td,
        .distance-row:nth-child(even) th { background: #f8fafc; }

        .group-total td {
            color: #1e3a8a;
            background: #eff6ff;
            font-weight: bold;
        }

        .grand-total td {
            color: #ffffff;
            background: #1e3a8a;
            font-weight: bold;
        }

        .zero { color: #94a3b8; }

        .footer {
            margin-top: 10px;
            color: #64748b;
            font-size: 7px;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="logo">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="Province of Davao de Oro seal">
                @endif
            </td>
            <td>
                <div class="agency">Provincial Government of Davao de Oro</div>
                <h1>Shirt Size Inventory Report</h1>
                <div class="event">Kalamboan PADAC Dagan Kontra Droga {{ $report['year'] }}</div>
            </td>
            <td class="meta">
                <strong>Registration year:</strong> {{ $report['year'] }}<br>
                <strong>Generated:</strong> {{ $report['generated_at'] }}
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td>
                <div class="summary-label">Categories and subcategories</div>
                <div class="summary-value">{{ number_format(count($report['groups'])) }}</div>
            </td>
            <td>
                <div class="summary-label">Total shirts</div>
                <div class="summary-value">{{ number_format($report['grand_total']) }}</div>
            </td>
            @foreach ($report['size_labels'] as $size => $label)
                <td>
                    <div class="summary-label">{{ $label }}</div>
                    <div class="summary-value">{{ number_format($report['grand_totals'][$size]) }}</div>
                </td>
            @endforeach
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th class="category">Main Category</th>
                <th class="subcategory">Subcategory</th>
                <th class="distance">Race Distance</th>
                @foreach ($report['size_labels'] as $label)
                    <th class="size">{{ $label }}</th>
                @endforeach
                <th class="total">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['groups'] as $group)
                @foreach ($group['distances'] as $distance)
                    <tr class="distance-row">
                        @if ($loop->first)
                            <td class="category" rowspan="4">{{ $group['category'] }}</td>
                            <td class="subcategory" rowspan="4">{{ $group['subcategory'] }}</td>
                        @endif
                        <td class="distance">{{ $distance['label'] }}</td>
                        @foreach ($report['size_labels'] as $size => $label)
                            @php($count = $distance['sizes'][$size])
                            <td @class(['zero' => $count === 0])>{{ number_format($count) }}</td>
                        @endforeach
                        <td class="total">{{ number_format($distance['total']) }}</td>
                    </tr>
                @endforeach
                <tr class="group-total">
                    <td class="distance">All Distances</td>
                    @foreach ($report['size_labels'] as $size => $label)
                        <td>{{ number_format($group['totals'][$size]) }}</td>
                    @endforeach
                    <td class="total">{{ number_format($group['total']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">No categories are configured for this report.</td>
                </tr>
            @endforelse
            <tr class="grand-total">
                <td colspan="3" style="text-align: right;">Grand Total</td>
                @foreach ($report['size_labels'] as $size => $label)
                    <td>{{ number_format($report['grand_totals'][$size]) }}</td>
                @endforeach
                <td class="total">{{ number_format($report['grand_total']) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        This report includes all configured main categories and their subcategories for {{ $report['year'] }}.
        Counts are grouped by race distance and shirt size.
    </div>
</body>
</html>
