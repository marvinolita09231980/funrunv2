<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>IN PLACE OF DUPLICATES</title>
    <style>
        @page { margin: 24px; }
        body { color: #111827; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        h1 { margin: 0; font-size: 17px; text-align: center; }
        .report-meta { margin: 5px 0 14px; color: #4b5563; text-align: center; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 6px; border: 1px solid #9ca3af; overflow-wrap: break-word; vertical-align: top; }
        thead { display: table-header-group; }
        .group-original, .group-replacement { color: #fff; font-size: 11px; text-align: center; }
        .group-original { background: #b45309; }
        .group-replacement { background: #15803d; }
        .column-heading { background: #e5e7eb; font-weight: bold; text-align: center; }
        tbody tr:nth-child(even) td { background: #f9fafb; }
        .empty-value { color: #6b7280; font-style: italic; }
    </style>
</head>
<body>
    <h1>IN PLACE OF DUPLICATES</h1>
    <div class="report-meta">
        Registration year {{ $year }} &middot;
        {{ $rows->count() }} replacement{{ $rows->count() === 1 ? '' : 's' }} &middot;
        Generated {{ $generatedAt->format('F j, Y g:i A') }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="group-original" colspan="5">ORIGINAL DATA</th>
                <th class="group-replacement" colspan="4">REPLACED DATA</th>
            </tr>
            <tr>
                @foreach (['Name', 'Gender', 'Category', 'Subcategory', 'Assigned Focal / AO'] as $heading)
                    <th class="column-heading">{{ $heading }}</th>
                @endforeach
                @foreach (['Name', 'Gender', 'Category', 'Subcategory'] as $heading)
                    <th class="column-heading">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach (['Name', 'Gender', 'Category', 'Subcategory', 'Assigned Focal / AO'] as $field)
                        <td class="{{ filled($row['original'][$field]) ? '' : 'empty-value' }}">
                            {{ filled($row['original'][$field]) ? $row['original'][$field] : 'Not provided' }}
                        </td>
                    @endforeach
                    @foreach (['Name', 'Gender', 'Category', 'Subcategory'] as $field)
                        <td class="{{ filled($row['replacement'][$field]) ? '' : 'empty-value' }}">
                            {{ filled($row['replacement'][$field]) ? $row['replacement'][$field] : 'Not provided' }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
