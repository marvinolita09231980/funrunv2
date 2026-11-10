<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Potential Duplicate Participants {{ $report['year'] }}</title>
    <style>
        @page { margin: 24px 28px; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            line-height: 1.35;
        }

        h1 {
            margin: 0 0 4px;
            color: #7c2d12;
            font-size: 19px;
        }

        .event {
            color: #c2410c;
            font-size: 9px;
            font-weight: bold;
        }

        .meta {
            margin-top: 7px;
            color: #64748b;
            font-size: 8px;
        }

        .summary {
            width: 100%;
            margin: 14px 0;
            border-collapse: separate;
            border-spacing: 8px 0;
        }

        .summary td {
            width: 33.333%;
            padding: 8px 10px;
            border: 1px solid #fed7aa;
            background: #fff7ed;
        }

        .summary-label {
            color: #9a3412;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .summary-value {
            margin-top: 2px;
            color: #7c2d12;
            font-size: 14px;
            font-weight: bold;
        }

        .group {
            margin: 0 0 12px;
            page-break-inside: avoid;
        }

        .group-title {
            padding: 6px 8px;
            color: #7c2d12;
            background: #ffedd5;
            border: 1px solid #fed7aa;
            font-size: 9px;
            font-weight: bold;
        }

        table.records {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .records thead { display: table-header-group; }
        .records tr { page-break-inside: avoid; }

        .records th,
        .records td {
            padding: 6px 5px;
            border: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
            overflow-wrap: break-word;
        }

        .records th {
            color: #fff;
            background: #1f2937;
            font-size: 7px;
            text-transform: uppercase;
        }

        .records tr:nth-child(even) td { background: #f8fafc; }

        .number { width: 3%; text-align: center !important; }
        .name { width: 16%; font-weight: bold; }
        .birth-date { width: 9%; }
        .age { width: 5%; text-align: center !important; }
        .address { width: 24%; }
        .phone { width: 12%; }
        .registration { width: 21%; }

        .empty {
            padding: 24px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }

        .note {
            margin-top: 12px;
            color: #64748b;
            font-size: 7px;
        }
    </style>
</head>
<body>
    <h1>Potential Duplicate Participants</h1>
    <div class="event">Kalamboan PADAC Dagan Kontra Droga {{ $report['year'] }}</div>
    <div class="meta">
        Registration year: {{ $report['year'] }} &bull;
        Age as of: {{ $report['as_of'] }} &bull;
        Generated: {{ $report['generated_at'] }}
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="summary-label">Potential Duplicate Groups</div>
                <div class="summary-value">{{ number_format($report['group_count']) }}</div>
            </td>
            <td>
                <div class="summary-label">Records for Review</div>
                <div class="summary-value">{{ number_format($report['record_count']) }}</div>
            </td>
            <td>
                <div class="summary-label">Report Status</div>
                <div class="summary-value">For Verification</div>
            </td>
        </tr>
    </table>

    @forelse ($report['groups'] as $group)
        <section class="group">
            <div class="group-title">
                Match Group: {{ $group['last_name'] }} &bull; Birth Date {{ $group['birth_date'] }}
            </div>

            <table class="records">
                <thead>
                    <tr>
                        <th class="number">#</th>
                        <th class="name">Name</th>
                        <th class="birth-date">Birth Date</th>
                        <th class="age">Age</th>
                        <th class="address">Address</th>
                        <th class="phone">Phone Number</th>
                        <th class="registration">Registration</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['records'] as $record)
                        <tr>
                            <td class="number">{{ $loop->iteration }}</td>
                            <td class="name">{{ $record['name'] }}</td>
                            <td>{{ $record['birth_date'] }}</td>
                            <td class="age">{{ $record['age'] ?? '—' }}</td>
                            <td>{{ $record['address'] }}</td>
                            <td>{{ $record['phone_number'] }}</td>
                            <td>
                                {{ $record['category'] }}<br>
                                <span style="color: #64748b;">{{ $record['subcategory'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @empty
        <div class="empty">No potential duplicate participants were found for {{ $report['year'] }}.</div>
    @endforelse

    <p class="note">
        This report contains personal information for authorized duplicate-registration review only.
        Verify each record before correcting or deleting a registration.
    </p>
</body>
</html>
