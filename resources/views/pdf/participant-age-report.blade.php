<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Participant Age Reports {{ $report['year'] }}</title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 6px; color: #92400e; }
        h2 { font-size: 16px; margin: 18px 0 12px; }
        .meta { font-size: 9px; color: #64748b; line-height: 1.7; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 9px 7px; text-align: left; border-bottom: 1px solid #e5e7eb; overflow-wrap: break-word; }
        th { background: #1f2937; color: white; }
        tr:nth-child(even) { background: #f8fafc; }
        tr { page-break-inside: avoid; }
        .rank { width: 5%; } .name { width: 25%; } .date { width: 12%; } .age { width: 6%; } .category { width: 20%; }
        .section + .section { page-break-before: always; }
        .note { font-size: 9px; color: #64748b; margin-top: 14px; }
    </style>
</head>
<body>
    @foreach ($report['sections'] as $section)
        <div class="section">
            <h1>PADAC Fun Run {{ $report['year'] }}</h1>
            <div class="meta">Registration year: {{ $report['year'] }} · Age as of: {{ $report['as_of'] }}<br>Coverage: {{ $report['scope'] }}<br>Generated: {{ $report['generated_at'] }}</div>
            <h2>{{ $section['title'] }}</h2>
            <table>
                <thead><tr><th class="rank">Rank</th><th class="name">Participant</th><th class="date">Birth Date</th><th class="age">Age</th><th class="category">Category</th><th>Subcategory</th></tr></thead>
                <tbody>
                    @forelse ($section['participants'] as $participant)
                        <tr><td>{{ $participant['rank'] }}</td><td>{{ $participant['name'] }}</td><td>{{ $participant['birth_date'] }}</td><td>{{ $participant['age'] }}</td><td>{{ $participant['category'] }}</td><td>{{ $participant['subcategory'] }}</td></tr>
                    @empty
                        <tr><td colspan="6">No eligible participants registered in this group.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <p class="note">{{ count($section['participants']) }} participant(s) listed. Sorted by birth date; matching birth dates are ordered by last name, first name, then record ID. Dates use MM/DD/YYYY.</p>
        </div>
    @endforeach
</body>
</html>
