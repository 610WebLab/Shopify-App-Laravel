<!DOCTYPE html>
<html>

<head>
    <title>Return label</title>
    <style>
        /* Adjust CSS for image */
        body {
            margin: 0;
            padding: 0;
        }

        img {
            max-width: 100%;
            max-height: 100%;
            /* Ensure image fills the available space */
            display: block;
            margin: auto;
            /* Center the image */
            page-break-inside: avoid;
            /* Ensure image doesn't break across pages */
        }
    </style>
</head>

<body>
    <div>
        <img src="{{ $imagePath }}" alt="Image">
    </div>
</body>

</html>
