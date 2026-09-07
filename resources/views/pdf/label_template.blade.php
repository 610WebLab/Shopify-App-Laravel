<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Label Preview{{ !empty($orderLabel) ? ' — ' . $orderLabel : '' }}</title>
    <style>
        @page {
            margin: 12mm;
        }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #111111;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        .preview-shell {
            width: 100%;
        }
        .preview-meta {
            font-size: 10px;
            color: #666666;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            margin-bottom: 10px;
            border-bottom: 1px solid #e5e5e5;
            padding-bottom: 6px;
        }
        .preview-body {
            width: 100%;
        }
        .preview-body img {
            max-width: 100%;
        }
        .preview-body table {
            border-collapse: collapse;
        }
    </style>
</head>
<body>
    <div class="preview-shell">
        @if (!empty($orderLabel))
            <div class="preview-meta">Label preview · Order {{ $orderLabel }}</div>
        @endif
        <div class="preview-body">
            {!! $templateContent !!}
        </div>
    </div>
</body>
</html>
