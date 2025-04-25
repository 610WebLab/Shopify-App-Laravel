<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Label Template</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; padding: 20px; }
        .container { border: 1px solid #000; padding: 20px; width: 100%; }
        .header { text-align: center; margin-bottom: 15px; }
        .details { margin-top: 10px; }
        .details p { margin: 4px 0; }
        .qr-code { margin-top: 15px; text-align: center; }
        .qr-code img { width: 100px; border: 1px solid #ccc; padding: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="details">
        {!! $templateContent !!}
        </div>

        </div>
    </div>
</body>
</html>
