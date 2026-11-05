<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Receipt</title>
    <style>
        @page { margin: 34px; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #1f2937;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        .receipt {
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 26px;
        }

        .header { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: middle; }
        .logo { width: 70px; }
        .logo img { width: 58px; height: 58px; }
        .agency { color: #6b7280; font-size: 10px; letter-spacing: .6px; text-transform: uppercase; }
        h1 { margin: 3px 0 1px; color: #111827; font-size: 19px; }
        .event { color: #92400e; font-size: 12px; font-weight: bold; }

        .status {
            margin: 22px 0;
            border: 1px solid #a7f3d0;
            border-radius: 7px;
            background: #ecfdf5;
            padding: 12px 14px;
            color: #065f46;
        }

        .status-title { font-size: 13px; font-weight: bold; }
        .status-copy { margin-top: 2px; color: #047857; }

        .meta { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .meta td { padding: 3px 0; }
        .meta-label { width: 125px; color: #6b7280; }
        .meta-value { color: #111827; font-weight: bold; }

        .section-title {
            margin: 18px 0 8px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            color: #111827;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .45px;
        }

        .details { width: 100%; border-collapse: collapse; }
        .details td {
            width: 50%;
            border: 1px solid #e5e7eb;
            padding: 9px 11px;
            vertical-align: top;
        }

        .label { margin-bottom: 3px; color: #6b7280; font-size: 9px; text-transform: uppercase; }
        .value { color: #111827; font-size: 11px; font-weight: bold; }

        .participants { width: 100%; border-collapse: collapse; }
        .participants th,
        .participants td {
            border: 1px solid #e5e7eb;
            padding: 8px 10px;
            text-align: left;
        }
        .participants th {
            background: #f9fafb;
            color: #6b7280;
            font-size: 9px;
            text-transform: uppercase;
        }
        .participants .number { width: 42px; text-align: center; }
        .participants .gender { width: 100px; }
        .participants .shirt-size { width: 80px; text-align: center; }
        .participants td { color: #111827; font-weight: bold; }

        .footer {
            margin-top: 24px;
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
            color: #6b7280;
            font-size: 9px;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $fullName = trim(collect([
            $participant->firstName,
            $participant->middleInitial ? $participant->middleInitial . '.' : null,
            $participant->lastName,
        ])->filter()->implode(' '));
        $receiptNumber = sprintf('KFR-%s-%06d', $participant->year, $participant->id);
        $registeredAt = $participant->created_at
            ? $participant->created_at->format('F d, Y h:i A')
            : 'Not recorded';
        $registrationCount = 1 + $additionalParticipants->count();
    @endphp

    <div class="receipt">
        <table class="header">
            <tr>
                <td class="logo">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="Province of Davao de Oro seal">
                    @endif
                </td>
                <td>
                    <div class="agency">Provincial Government of Davao de Oro</div>
                    <h1>Registration Receipt</h1>
                    <div class="event">Kalamboan PADAC Dagan Kontra Droga {{ $participant->year }}</div>
                </td>
            </tr>
        </table>

        <div class="status">
            <div class="status-title">Registration confirmed</div>
            <div class="status-copy">This participant's fun run registration has been successfully recorded.</div>
        </div>

        <table class="meta">
            <tr>
                <td class="meta-label">Receipt number</td>
                <td class="meta-value">{{ $receiptNumber }}</td>
            </tr>
            <tr>
                <td class="meta-label">Participant number</td>
                <td class="meta-value">{{ $participant->participantNumber ?: $participant->id }}</td>
            </tr>
            <tr>
                <td class="meta-label">Registration year</td>
                <td class="meta-value">{{ $participant->year }}</td>
            </tr>
            <tr>
                <td class="meta-label">Date registered</td>
                <td class="meta-value">{{ $registeredAt }}</td>
            </tr>
            <tr>
                <td class="meta-label">Registered participants</td>
                <td class="meta-value">{{ $registrationCount }}</td>
            </tr>
        </table>

        <div class="section-title">Participant Information</div>
        <table class="details">
            <tr>
                <td>
                    <div class="label">Full name</div>
                    <div class="value">{{ $fullName }}</div>
                </td>
                <td>
                    <div class="label">Registration status</div>
                    <div class="value">Registered</div>
                </td>
            </tr>
        </table>

        @if ($additionalParticipants->isNotEmpty())
            <div class="section-title">Additional Participants</div>
            <table class="participants">
                <thead>
                    <tr>
                        <th class="number">No.</th>
                        <th>Full name</th>
                        <th class="gender">Gender</th>
                        <th class="shirt-size">Shirt Size</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($additionalParticipants as $additionalParticipant)
                        @php
                            $additionalParticipantName = trim(collect([
                                $additionalParticipant->firstName,
                                $additionalParticipant->middleInitial
                                    ? $additionalParticipant->middleInitial . '.'
                                    : null,
                                $additionalParticipant->lastName,
                            ])->filter()->implode(' '));
                        @endphp
                        <tr>
                            <td class="number">{{ $loop->iteration }}</td>
                            <td>{{ $additionalParticipantName }}</td>
                            <td class="gender">
                                {{ filled($additionalParticipant->gender)
                                    ? ucwords(str_replace('-', ' ', $additionalParticipant->gender))
                                    : 'Not provided' }}
                            </td>
                            <td class="shirt-size">
                                {{ filled($additionalParticipant->shirtSize)
                                    ? strtoupper($additionalParticipant->shirtSize)
                                    : 'Not provided' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="section-title">Fun Run Information</div>
        <table class="details">
            <tr>
                <td>
                    <div class="label">Category</div>
                    <div class="value">{{ $participant->categoryDescription ?: 'Not provided' }}</div>
                </td>
                <td>
                    <div class="label">Race distance</div>
                    <div class="value">{{ filled($participant->distanceCategory) ? strtoupper($participant->distanceCategory) : 'Not provided' }}</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="label">Organization / Description</div>
                    <div class="value">{{ $participant->subDescription ?: 'Not provided' }}</div>
                </td>
                <td>
                    <div class="label">Shirt size</div>
                    <div class="value">{{ filled($participant->shirtSize) ? strtoupper($participant->shirtSize) : 'Not provided' }}</div>
                </td>
            </tr>
        </table>

        <div class="footer">
            Keep this receipt for your records and present it when requested by the event organizers.<br>
            Generated electronically; no signature is required.
        </div>
    </div>
</body>
</html>
