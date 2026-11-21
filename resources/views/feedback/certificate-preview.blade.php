<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Certificate Preview</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #f3f4f6;
            color: #111827;
            font-family: Arial, sans-serif;
        }

        .page {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 1px solid #d1d5db;
            background: #ffffff;
            padding: 14px 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
        }

        h1 { margin: 0; font-size: 18px; }
        p { margin: 4px 0 0; color: #6b7280; font-size: 13px; }

        .download {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: #166534;
            padding: 10px 16px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .download:hover { background: #14532d; }

        .preview {
            flex: 1;
            min-height: 0;
            padding: 16px;
        }

        iframe {
            width: 100%;
            height: calc(100vh - 104px);
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #ffffff;
        }

        @media (max-width: 640px) {
            .toolbar { align-items: stretch; flex-direction: column; }
            .download { width: 100%; }
            .preview { padding: 8px; }
            iframe { height: calc(100vh - 154px); }
        }
    </style>
</head>
<body>
    <main class="page">
        <header class="toolbar">
            <div>
                <h1>Certificate Preview</h1>
                <p>
                    {{ $participant->firstName }} {{ $participant->lastName }} — review your certificate before downloading.
                </p>
            </div>
            <a class="download" href="{{ $downloadUrl }}">Download PDF</a>
        </header>

        <section class="preview">
            <iframe src="{{ $inlineUrl }}" title="Finisher certificate preview">
                <p>
                    PDF preview is unavailable.
                    <a href="{{ $downloadUrl }}">Download the certificate instead.</a>
                </p>
            </iframe>
        </section>
    </main>
</body>
</html>
