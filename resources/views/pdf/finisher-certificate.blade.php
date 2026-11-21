<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Finisher's Certificate</title>
    <style>
        @page { margin: 0; }

        * { box-sizing: border-box; }

        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: DejaVu Sans, sans-serif;
        }

        .certificate {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .certificate-template {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .runner-name {
            position: absolute;
            top: 42.7%;
            left: 15%;
            width: 70%;
            color: #000000;
            font-size: {{ $nameFontSize }}pt;
            font-weight: 700;
            line-height: 1.05;
            text-align: center;
        }

        .distance {
            position: absolute;
            top: 53.85%;
            left: 51%;
            width: 6.3%;
            color: #000000;
            font-size: 17pt;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
        }

        .distance.distance-long {
            top: 53.3%;
            font-size: 8.25pt;
            line-height: .9;
            white-space: normal;
        }

        .distance.distance-ten {
            top: 53.85%;
            font-size: 12pt;
        }

        .distance.distance-five {
            top: 53.2%;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <img
            class="certificate-template"
            src="{{ $templateDataUri }}"
            alt=""
        >
        <div class="runner-name">{{ $fullName }}</div>
        @if ($distance === 'Rave Run (3 km)')
            <div class="distance distance-long">Rave Run<br>(3 km)</div>
        @else
            <div @class([
                'distance',
                'distance-five' => $distance === '5 km',
                'distance-ten' => $distance === '10 km',
            ])>{{ $distance }}</div>
        @endif
    </div>
</body>
</html>
