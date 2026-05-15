<?php
$errorPageTitle = $errorPageTitle ?? '500 | Server Error';
$errorPageHeading = $errorPageHeading ?? 'Server Error';
$errorPageMessage = $errorPageMessage ?? 'Something failed during startup or request handling. Check your configuration and server logs.';
$errorPageActionHref = $errorPageActionHref ?? './';
$errorPageActionLabel = $errorPageActionLabel ?? 'Back to Home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($errorPageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: "Instrument Sans", "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top left, rgba(37, 99, 235, 0.08), transparent 28%),
                #f8fafc;
            color: #111827;
        }
        .panel {
            width: min(620px, 100%);
            padding: 32px;
            border-radius: 28px;
            background: #fff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 18px 40px rgba(15, 39, 71, 0.08);
        }
        .code {
            width: 72px;
            height: 72px;
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(15, 39, 71, 0.08);
            color: #102a43;
            font-size: 28px;
            font-weight: 800;
        }
        h1 {
            margin: 18px 0 10px;
            color: #102a43;
            font-size: clamp(2rem, 4vw, 3rem);
        }
        p {
            color: #6b7280;
            line-height: 1.8;
            margin: 0;
        }
        a {
            display: inline-flex;
            margin-top: 24px;
            text-decoration: none;
            border-radius: 16px;
            background: #0f2747;
            color: #fff;
            padding: 12px 18px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <section class="panel">
        <div class="code">500</div>
        <h1><?= htmlspecialchars($errorPageHeading, ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars($errorPageMessage, ENT_QUOTES, 'UTF-8') ?></p>
        <a href="<?= htmlspecialchars($errorPageActionHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($errorPageActionLabel, ENT_QUOTES, 'UTF-8') ?></a>
    </section>
</body>
</html>
