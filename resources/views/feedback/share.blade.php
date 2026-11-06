<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>PADAC Fun Run 2026 Feedback Form</title>

    <meta property="og:type" content="website" />
    <meta property="og:title" content="PADAC Fun Run 2026 Feedback Form" />
    <meta property="og:description" content="Tap to open the PADAC Fun Run 2026 feedback form." />
    <meta property="og:url" content="{{ $shareUrl }}" />
    <meta property="og:image" content="{{ $previewImageUrl }}" />
    <meta property="og:image:type" content="image/png" />
    <meta property="og:image:alt" content="QR code for the PADAC Fun Run 2026 feedback form" />
    <meta property="og:site_name" content="PADAC Fun Run 2026" />

    <style>
        * { box-sizing: border-box; }
        body {
            display: grid;
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            place-items: center;
            padding: 1rem;
            color: #292524;
            font-family: ui-sans-serif, system-ui, sans-serif;
            text-align: center;
            background: radial-gradient(circle at top, #fff7d6, #fef3c7 45%, #fde68a);
        }
        main {
            width: min(100%, 28rem);
            padding: 1.5rem;
            border: 1px solid rgba(180, 83, 9, 0.2);
            border-radius: 1.25rem;
            background: #ffffff;
            box-shadow: 0 18px 45px rgba(69, 43, 2, 0.14);
        }
        img {
            width: min(13rem, 65vw);
            height: auto;
            border-radius: 0.8rem;
        }
        h1 { margin: 1rem 0 0; font-size: 1.35rem; }
        p { margin: 0.65rem 0 1.25rem; color: #57534e; line-height: 1.6; }
        a {
            display: inline-flex;
            min-height: 2.75rem;
            align-items: center;
            justify-content: center;
            padding: 0.7rem 1rem;
            border-radius: 0.75rem;
            color: #ffffff;
            font-weight: 700;
            text-decoration: none;
            background: #b45309;
        }
    </style>
</head>
<body>
    <main>
        <img src="{{ $previewImageUrl }}" alt="QR code for the PADAC Fun Run 2026 feedback form" />
        <h1>PADAC Fun Run 2026 Feedback</h1>
        <p>Opening the feedback form. If it does not open automatically, use the button below.</p>
        <a href="{{ $feedbackUrl }}">Open Feedback Form</a>
    </main>

    <script>
        window.location.replace(@js($feedbackUrl));
    </script>
</body>
</html>
