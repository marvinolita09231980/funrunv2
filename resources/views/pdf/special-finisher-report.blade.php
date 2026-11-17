<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $report['title'] }} {{ $report['year'] }}</title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { margin: 0 0 6px; color: #92400e; font-size: 20px; }
        h2 { margin: 18px 0 12px; font-size: 16px; }
        .meta { color: #64748b; font-size: 9px; line-height: 1.7; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 9px 7px; border: 1px solid #cbd5e1; text-align: left; overflow-wrap: break-word; }
        th { color: #fff; background: #1f2937; }
        tr:nth-child(even) { background: #f8fafc; }
        tr { page-break-inside: avoid; }
        .rank { width: 5%; } .bib { width: 8%; } .name { width: 20%; } .gender { width: 8%; }
        .distance { width: 11%; } .time { width: 12%; } .category { width: 16%; } .subcategory { width: 20%; }
        .note { margin-top: 14px; color: #64748b; font-size: 9px; }
    </style>
</head>
<body>
    <h1>PADAC Fun Run {{ $report['year'] }}</h1>
    <div class="meta">Event year: {{ $report['year'] }} · Generated: {{ $report['generated_at'] }}</div>
    <h2>{{ $report['title'] }}</h2>
    <table>
        <thead>
            <tr>
                <th class="rank">Rank</th>
                <th class="bib">Race Bib</th>
                <th class="name">Finisher</th>
                <th class="gender">Gender</th>
                <th class="distance">Distance</th>
                <th class="time">Finish Time</th>
                <th class="category">Category</th>
                <th class="subcategory">Subcategory</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report['finishers'] as $finisher)
                <tr>
                    <td>{{ $finisher['rank'] }}</td>
                    <td>{{ $finisher['race_bib'] }}</td>
                    <td>{{ $finisher['name'] }}</td>
                    <td>{{ $finisher['gender'] }}</td>
                    <td>{{ $finisher['distance'] }}</td>
                    <td>{{ $finisher['finish_time'] }}</td>
                    <td>{{ $finisher['category'] }}</td>
                    <td>{{ $finisher['subcategory'] }}</td>
                </tr>
            @empty
                <tr><td colspan="8">No eligible finishers are recorded for this report.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="note">{{ count($report['finishers']) }} finisher(s) listed. Ranked by earliest recorded finish time.</p>
</body>
</html>
