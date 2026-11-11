<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $report['title'] }} {{ $report['year'] }}</title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 6px; color: #92400e; }
        h2 { font-size: 16px; margin: 12px 0; }
        .meta { font-size: 9px; color: #64748b; line-height: 1.7; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 8px; text-align: left; border: 1px solid #cbd5e1; overflow-wrap: break-word; }
        thead { display: table-header-group; }
        th { background: #1f2937; color: white; }
        tr { page-break-inside: avoid; }
        .group td { background: #fef3c7; color: #92400e; font-weight: bold; }
        .number { width: 5%; } .name { width: 25%; } .birth-date { width: 11%; } .phone { width: 14%; } .gender { width: 8%; } .distance { width: 10%; } .shirt { width: 9%; } .time { width: 12%; }
    </style>
</head>
<body>
    <h1>PADAC Fun Run {{ $report['year'] }}</h1>
    <h2>{{ $report['title'] }}</h2>
    <div class="meta">Registration year: {{ $report['year'] }} · Generated: {{ $report['generated_at'] }}<br>{{ count($report['groups']) }} registration group(s) · {{ $report['total_runners'] }} runner(s)</div>
    <table>
        <thead><tr><th class="number">No.</th><th class="name">Runner</th><th class="birth-date">Birth Date</th><th class="phone">Phone Number</th><th class="gender">Gender</th><th class="distance">Distance</th><th class="shirt">Shirt Size</th><th class="time">Time</th></tr></thead>
        <tbody>
            @forelse ($report['groups'] as $group)
                <tr class="group"><td colspan="8">{{ $loop->iteration }}. Registration #{{ $group['reference'] }} · {{ $group['category'] }} · {{ count($group['members']) }} runner(s)</td></tr>
                @foreach ($group['members'] as $member)
                    <tr><td>{{ $loop->iteration }}</td><td>{{ $member['name'] }}</td><td>{{ $member['birth_date'] }}</td><td>{{ $member['phone_number'] }}</td><td>{{ $member['gender'] }}</td><td>{{ $member['distance'] }}</td><td>{{ $member['shirt_size'] }}</td><td></td></tr>
                @endforeach
            @empty
                <tr><td colspan="8">No {{ strtolower($report['title']) }} registered for the selected year.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
