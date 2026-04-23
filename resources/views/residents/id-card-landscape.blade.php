<!-- resources/views/residents/id-card-landscape.blade.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident ID Card - {{ $resident->full_name }}</title>
    <style>
        @page {
            size: 3.375in 2.125in;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', 'Helvetica', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* Floating Sidebar */
        .floating-sidebar {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 20px;
            width: 300px;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 1000;
            cursor: move;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
            cursor: move;
        }

        .sidebar-title {
            font-weight: 700;
            font-size: 16px;
            color: #1f2937;
        }

        .drag-handle {
            cursor: move;
            color: #9ca3af;
            font-size: 20px;
        }

        .control-section {
            margin-bottom: 15px;
        }

        .control-label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .control-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .sidebar-btn {
            flex: 1;
            min-width: 80px;
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.2s;
        }

        .sidebar-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-rotate {
            background-color: #10b981;
            color: white;
        }

        .btn-rotate:hover {
            background-color: #059669;
        }

        .btn-flip {
            background-color: #8b5cf6;
            color: white;
        }

        .btn-flip:hover {
            background-color: #7c3aed;
        }

        .btn-resize {
            background-color: #f59e0b;
            color: white;
        }

        .btn-resize:hover {
            background-color: #d97706;
        }

        .btn-move {
            background-color: #3b82f6;
            color: white;
        }

        .btn-move:hover {
            background-color: #2563eb;
        }

        .btn-move.active {
            background-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .btn-crop {
            background-color: #ec4899;
            color: white;
        }

        .btn-crop:hover {
            background-color: #db2777;
        }

        .btn-crop.active {
            background-color: #be185d;
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.3);
        }

        .btn-reset {
            background-color: #ef4444;
            color: white;
            width: 100%;
        }

        .btn-reset:hover {
            background-color: #dc2626;
        }

        .btn-print {
            background-color: #2563eb;
            color: white;
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
        }

        .btn-print:hover {
            background-color: #1d4ed8;
        }

        .btn-back {
            background-color: #4b5563;
            color: white;
            width: 100%;
            padding: 10px;
            text-decoration: none;
            display: block;
            text-align: center;
        }

        .btn-back:hover {
            background-color: #374151;
        }

        .card-container {
            display: flex;
            flex-direction: column;
            gap: 40px;
        }

        /* ID Card Front Side */
        .id-card-front {
            width: 3.375in;
            height: 2.125in;
            background-image: url('{{ asset('storage/images/ID_lgu2FRONT5noname.png') }}');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            border-radius: 0;
            overflow: visible;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
            position: relative;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }

        /* Photo Container - positioned on left side */
        .photo-container {
            position: absolute;
            left: 0.17in;
            top: 0.695in;
            width: 0.86in;
            height: 0.87in;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ID Number positioned below photo */
        .id-number {
            position: absolute;
            left: 0.38in;
            bottom: 0.4in;
            font-size: 0.07in;
            font-weight: 700;
            color: #000;
            text-align: left;
            width: 1.05in;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Signature area */
        .signature-container {
            position: absolute;
            display: flex;
            overflow: visible !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            transition: none;
        }

        .signature-container.draggable {
            cursor: move;
            outline: 2px dashed #3b82f6;
            outline-offset: 2px;
        }

        .signature-container.cropping {
            cursor: crosshair;
            outline: 2px dashed #ec4899;
            outline-offset: 2px;
        }

        .signature-container img {
            width: 120%;
            height: 100%;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            filter: contrast(1.3) brightness(0.85);
            transition: transform 0.3s ease;
            transform-origin: center center;
        }

        /* Crop overlay */
        .crop-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .crop-overlay.active {
            display: flex;
        }

        .crop-container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            max-width: 90vw;
            max-height: 90vh;
            overflow: auto;
        }

        .crop-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }

        .crop-title {
            font-weight: 700;
            font-size: 18px;
            color: #1f2937;
        }

        .crop-canvas-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }

        .crop-canvas {
            border: 2px solid #e5e7eb;
            cursor: crosshair;
            display: block;
        }

        .crop-selection {
            position: absolute;
            border: 2px dashed #ec4899;
            background: rgba(236, 72, 153, 0.1);
            pointer-events: none;
        }

        .crop-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .crop-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .crop-btn-apply {
            background-color: #10b981;
            color: white;
        }

        .crop-btn-apply:hover {
            background-color: #059669;
        }

        .crop-btn-cancel {
            background-color: #6b7280;
            color: white;
        }

        .crop-btn-cancel:hover {
            background-color: #4b5563;
        }

        /* Data fields positioned over white areas */
        .data-field {
            position: absolute;
            font-weight: 700;
            color: #000;
            line-height: 1.1;
            overflow: hidden;
            text-overflow: ellipsis;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            transition: none;
        }

        .data-field.draggable {
            cursor: move;
            outline: 2px dashed #f59e0b;
            outline-offset: 2px;
        }

        /* Last Name field */
        .field-lastname {
            left: 1.29in;
            top: 0.78in;
            width: 1.85in;
            height: 0.14in;
            font-size: 0.08in;
        }

        /* Given Names field */
        .field-firstname {
            left: 1.29in;
            top: 1.02in;
            width: 1.85in;
            height: 0.14in;
            font-size: 0.08in;
        }

        /* Middle Name field */
        .field-middlename {
            left: 1.29in;
            top: 1.265in;
            width: 1.85in;
            height: 0.14in;
            font-size: 0.08in;
        }

        /* Date of Birth field */
        .field-birthdate {
            left: 1.29in;
            top: 1.51in;
            width: 1.85in;
            height: 0.14in;
            font-size: 0.08in;
        }

        /* Address field */
        .field-address {
            left: 1.29in;
            top: 1.75in;
            width: 1.85in;
            height: 0.45in;
            font-size: 0.06in;
            text-transform: uppercase;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Back Side - HTML/CSS */
        .id-card-back {
            width: 3.375in;
            height: 2.125in;
            background-color: #f5f5f5;
            border-radius: 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: visible;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color-adjust: exact;
        }

        /* Back logos at top */
        .back-logos {
            position: absolute;
            top: 0.1in;
            left: 0.6in;
            display: flex;
            filter: grayscale(100%);
            gap: 0.15in;
            align-items: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .back-logo {
            width: 0.25in;
            height: 0.25in;
        }

        .back-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Back labels and underlines */
        .back-field-container {
            position: absolute;
        }

        .back-field-label {
            font-size: 0.07in;
            font-style: italic;
            color: #000;
            margin-bottom: 0.02in;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .back-field-underline {
            width: 1.4in;
            height: 0.01in;
            background-color: #000;
            margin-bottom: 0.015in;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .back-data-field {
            font-size: 0.12in;
            font-weight: 400;
            color: #000;
            line-height: 1.1;
            max-width: 1.4in;
            word-wrap: break-word;
            overflow-wrap: break-word;
            overflow: hidden;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            transition: none;
        }

        .back-data-field.draggable {
            cursor: move;
            outline: 2px dashed #f59e0b;
            outline-offset: 2px;
        }

        /* Auto-resize for long text */
        .back-data-field.long-text {
            font-size: 0.08in;
        }

        .back-data-field.very-long-text {
            font-size: 0.06in;
        }

        /* Date Issue field */
        .field-date-issue-container {
            left: 0.15in;
            top: 0.35in;
        }

        /* Sex field */
        .field-sex-container {
            left: 0.15in;
            top: 0.60in;
        }

        /* Marital Status field */
        .field-marital-status-container {
            left: 0.15in;
            top: 0.85in;
        }

        /* Birthplace field */
        .field-birthplace-container {
            left: 0.15in;
            top: 1.10in;
        }

        /* Emergency Contact field */
        .field-emergency-container {
            left: 0.15in;
            top: 1.35in;
        }

        /* Occupation field */
        .field-occupation-container {
            left: 0.15in;
            top: 1.60in;
        }

        /* Special Sector field */
        .field-special-sector-container {
            left: 0.15in;
            top: 1.85in;
        }

        /* QR Code on back */
        .qr-code-back {
            position: absolute;
            right: 0.1in;
            top: 0.1in;
            width: 1.5in;
            height: 1.5in;
            background-color: white;
            padding: 0.03in;
            border: 1px solid #000;
            display: flex;
            justify-content: center;
            align-items: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .qr-code-back img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Mayor signature and name */
        .mayor-signature-section {
            position: absolute;
            right: 0.19in;
            bottom: 0.05in;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .mayor-signature {
            width: 1.2in;
            height: 0.25in;
            margin-bottom: -0.03in;
        }

        .mayor-signature img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .mayor-name {
            font-size: 0.07in;
            font-weight: 700;
            color: #000;
            text-align: center;
            padding-top: 0.02in;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .mayor-title {
            font-size: 0.07in;
            color: #000;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Field selector and font size controls */
        .field-selector,
        .font-size-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .font-size-controls {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .font-size-input {
            flex: 1;
            margin-bottom: 0;
        }

        .font-unit-select {
            width: 70px;
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 13px;
        }

        /* For printing */
        @media print {
            body {
                background: none;
                padding: 0;
                margin: 0;
            }

            .floating-sidebar,
            .crop-overlay {
                display: none !important;
            }

            .card-container {
                gap: 0;
            }

            .id-card-front {
                margin-bottom: 0;
                page-break-after: always;
                border-radius: 0;
                box-shadow: none;
                overflow: hidden;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            .signature-container,
            .data-field,
            .back-data-field {
                outline: none !important;
            }

            .id-card-back {
                page-break-before: always;
                border-radius: 0;
                box-shadow: none;
                overflow: hidden;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
                transform: rotate(180deg);
            }

            .back-data-field,
            .back-field-label,
            .mayor-name,
            .mayor-title {
                color: #000 !important;
                font-weight: 900 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .back-field-underline {
                background-color: #000 !important;
                border: 1px solid #000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            /* Force all elements to print with exact colors */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>
    @php
        // Initialize variables
        $signaturePath = null;
        $signatureStyle = '';
        $imageStyle = '';
        $filename = null;

        // Process signature: convert base64 to image file WITHOUT auto-cropping
        if ($resident->signature) {
            try {
                $signatureData = $resident->signature;

                // Check if signature is base64 or file path
                $isBase64 = str_starts_with($signatureData, 'data:image');

                if ($isBase64) {
                    // CASE 1: Base64 signature - process it

                    // Remove data:image/...;base64, prefix if present
                    $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);

                    // Decode base64
                    $imageData = base64_decode($signatureData);
                } else {
                    // CASE 2: File path signature - load the file

                    // Build full path (handle both with and without 'public/' prefix)
                    if (str_starts_with($signatureData, 'residents/signature/')) {
                        $fullPath = public_path($signatureData);
                    } else {
                        $fullPath = public_path('residents/signature/' . basename($signatureData));
                    }

                    // Check if file exists
                    if (!file_exists($fullPath)) {
                        throw new \Exception('Signature file not found: ' . $fullPath);
                    }

                    // Read the file
                    $imageData = file_get_contents($fullPath);
                }

                if ($imageData) {
                    // Create image from string (supports PNG, JPEG, GIF, etc.)
                    $image = imagecreatefromstring($imageData);

                    if ($image) {
                        // Get image dimensions
                        $width = imagesx($image);
                        $height = imagesy($image);

                        // Detect if this is a signature pad capture
                        $isSignaturePad = false;
                        $transparentCount = 0;
                        $whiteCount = 0;
                        $totalPixels = 0;

                        for ($i = 0; $i < 100; $i++) {
                            $rx = rand(0, $width - 1);
                            $ry = rand(0, $height - 1);
                            $rgb = imagecolorat($image, $rx, $ry);
                            $colors = imagecolorsforindex($image, $rgb);

                            if (isset($colors['alpha']) && $colors['alpha'] > 100) {
                                $transparentCount++;
                            }

                            $brightness = ($colors['red'] + $colors['green'] + $colors['blue']) / 3;
                            if ($brightness > 250) {
                                $whiteCount++;
                            }
                            $totalPixels++;
                        }

                        if (($transparentCount + $whiteCount) / $totalPixels > 0.7) {
                            $isSignaturePad = true;
                        }

                        // Create new image with transparency
                        $newImage = imagecreatetruecolor($width, $height);
                        imagealphablending($newImage, false);
                        imagesavealpha($newImage, true);
                        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
                        imagefill($newImage, 0, 0, $transparent);
                        imagealphablending($newImage, true);

                        // Collect signature pixels for processing
                        $shadowPixels = [];

                        if ($isSignaturePad) {
                            // For signature pad: look for any non-transparent, non-white pixels
                            for ($x = 0; $x < $width; $x++) {
                                for ($y = 0; $y < $height; $y++) {
                                    $rgb = imagecolorat($image, $x, $y);
                                    $colors = imagecolorsforindex($image, $rgb);

                                    $r = $colors['red'];
                                    $g = $colors['green'];
                                    $b = $colors['blue'];
                                    $alpha = isset($colors['alpha']) ? $colors['alpha'] : 0;
                                    $gray = ($r + $g + $b) / 3;

                                    $isVisible = $gray < 230 && $alpha < 120;

                                    if ($isVisible) {
                                        $shadowPixels[] = [$x, $y];
                                    }
                                }
                            }
                        } else {
                            // For scanned signatures: adaptive background removal
                            $bgSamples = [];
                            $samplePoints = [
                                [0, 0],
                                [$width - 1, 0],
                                [0, $height - 1],
                                [$width - 1, $height - 1],
                                [intval($width / 2), 0],
                                [intval($width / 2), $height - 1],
                                [0, intval($height / 2)],
                                [$width - 1, intval($height / 2)],
                            ];

                            foreach ($samplePoints as $point) {
                                $rgb = imagecolorat($image, $point[0], $point[1]);
                                $colors = imagecolorsforindex($image, $rgb);
                                $bgSamples[] = ($colors['red'] + $colors['green'] + $colors['blue']) / 3;
                            }

                            $avgBackground = array_sum($bgSamples) / count($bgSamples);
                            $threshold = $avgBackground > 200 ? 30 : $avgBackground * 0.7;

                            for ($x = 0; $x < $width; $x++) {
                                for ($y = 0; $y < $height; $y++) {
                                    $rgb = imagecolorat($image, $x, $y);
                                    $colors = imagecolorsforindex($image, $rgb);

                                    $r = $colors['red'];
                                    $g = $colors['green'];
                                    $b = $colors['blue'];
                                    $alpha = isset($colors['alpha']) ? $colors['alpha'] : 0;
                                    $gray = ($r + $g + $b) / 3;

                                    // Local background comparison
                                    $localBg = $gray;
                                    $sampleRadius = 5;
                                    $samples = 0;
                                    $sumBg = 0;

                                    for ($dx = -$sampleRadius; $dx <= $sampleRadius; $dx += $sampleRadius) {
                                        for ($dy = -$sampleRadius; $dy <= $sampleRadius; $dy += $sampleRadius) {
                                            $sx = $x + $dx;
                                            $sy = $y + $dy;
                                            if ($sx >= 0 && $sx < $width && $sy >= 0 && $sy < $height) {
                                                $srgb = imagecolorat($image, $sx, $sy);
                                                $sc = imagecolorsforindex($image, $srgb);
                                                $sumBg += ($sc['red'] + $sc['green'] + $sc['blue']) / 3;
                                                $samples++;
                                            }
                                        }
                                    }

                                    if ($samples > 0) {
                                        $localBg = $sumBg / $samples;
                                    }

                                    $isDark = $gray < $threshold;
                                    $isDarkerThanLocal = $localBg - $gray > 40;

                                    if (($isDark || $isDarkerThanLocal) && $alpha < 110) {
                                        $shadowPixels[] = [$x, $y];
                                    }
                                }
                            }

                            // Fallback for scanned signatures
                            if (empty($shadowPixels)) {
                                for ($x = 0; $x < $width; $x++) {
                                    for ($y = 0; $y < $height; $y++) {
                                        $rgb = imagecolorat($image, $x, $y);
                                        $colors = imagecolorsforindex($image, $rgb);
                                        $gray = ($colors['red'] + $colors['green'] + $colors['blue']) / 3;

                                        if ($gray < 180) {
                                            $shadowPixels[] = [$x, $y];
                                        }
                                    }
                                }
                            }
                        }

                        // Draw shadow/outline (thicker for signature pad)
                        $shadowSize = $isSignaturePad ? 2 : 1;
                        foreach ($shadowPixels as $pixel) {
                            [$px, $py] = $pixel;

                            for ($dx = -$shadowSize; $dx <= $shadowSize; $dx++) {
                                for ($dy = -$shadowSize; $dy <= $shadowSize; $dy++) {
                                    $nx = $px + $dx;
                                    $ny = $py + $dy;

                                    if ($nx >= 0 && $nx < $width && $ny >= 0 && $ny < $height) {
                                        $shadowColor = imagecolorallocatealpha($newImage, 10, 10, 10, 0);
                                        imagesetpixel($newImage, $nx, $ny, $shadowColor);
                                    }
                                }
                            }
                        }

                        // Draw the main signature on top (pure black)
                        foreach ($shadowPixels as $pixel) {
                            [$px, $py] = $pixel;
                            $blackColor = imagecolorallocatealpha($newImage, 0, 0, 0, 0);
                            imagesetpixel($newImage, $px, $py, $blackColor);
                        }

                        // Disable blending and save alpha
                        imagealphablending($newImage, false);
                        imagesavealpha($newImage, true);

                        // Save the processed image
                        $tempDir = storage_path('app/public/temp_signatures');
                        if (!file_exists($tempDir)) {
                            mkdir($tempDir, 0755, true);
                        }

                        // Generate unique filename
                        $filename = 'signature_' . $resident->id . '_' . time() . '.png';
                        $filePath = $tempDir . '/' . $filename;

                        // Save as PNG with maximum quality and transparency
                        imagepng($newImage, $filePath, 0);

                        // Free memory
                        imagedestroy($image);
                        imagedestroy($newImage);

                        // Set path for blade template
                        $signaturePath = asset('storage/temp_signatures/' . $filename);

                        // Default container sizing
                        $containerWidth = 0.75;
                        $containerHeight = 0.32;
                        $alignItems = 'center';
                        $justifyContent = 'center';
                        $bottomPosition = 0.06;
                        $leftPosition = 0.32;
                        $objectFit = 'contain';

                        // Generate dynamic CSS for container
                        $signatureStyle = "
                        width: {$containerWidth}in !important;
                        height: {$containerHeight}in !important;
                        left: {$leftPosition}in !important;
                        bottom: {$bottomPosition}in !important;
                        align-items: {$alignItems} !important;
                        justify-content: {$justifyContent} !important;
                        overflow: visible !important;
                    ";

                        // Generate dynamic CSS for image
                        $imageStyle = "
                        object-fit: {$objectFit} !important;
                        object-position: center center !important;
                    ";
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Signature processing error: ' . $e->getMessage());

                // Fallback: if it's a file path, use asset() helper
        if (!str_starts_with($resident->signature, 'data:image')) {
                    $signaturePath = asset($resident->signature);
                } else {
                    $signaturePath = $resident->signature;
                }
            }
        }
    @endphp

    <!-- Crop Overlay -->
    <div class="crop-overlay" id="cropOverlay">
        <div class="crop-container">
            <div class="crop-header">
                <span class="crop-title">Crop Signature</span>
            </div>
            <div class="crop-canvas-wrapper" id="cropCanvasWrapper">
                <canvas id="cropCanvas"></canvas>
                <div class="crop-selection" id="cropSelection"></div>
            </div>
            <div class="crop-actions">
                <button class="crop-btn crop-btn-cancel" onclick="cancelCrop()">Cancel</button>
                <button class="crop-btn crop-btn-apply" onclick="applyCrop()">Apply Crop</button>
            </div>
        </div>
    </div>

    <!-- Floating Sidebar -->
    <div class="floating-sidebar" id="floatingSidebar">
        <div class="sidebar-header">
            <span class="sidebar-title">ID Card Editor</span>
            <span class="drag-handle">⋮⋮</span>
        </div>

        <!-- Actions -->
        <div class="control-section">
            <button class="sidebar-btn btn-print" onclick="window.print()">🖨️ Print ID Card</button>
            <a href="{{ route('residents.show', $resident->id) }}" class="sidebar-btn btn-back">← Back</a>
        </div>

        <!-- Signature Tools -->
        <div class="control-section">
            <div class="control-label">Signature Transform</div>
            <div class="control-buttons">
                <button class="sidebar-btn btn-rotate" onclick="rotateSignature()">↻ Rotate</button>
                <button class="sidebar-btn btn-flip" onclick="flipHorizontal()">↔️ Flip H</button>
                <button class="sidebar-btn btn-flip" onclick="flipVertical()">↕️ Flip V</button>
            </div>
        </div>

        <div class="control-section">
            <div class="control-label">Signature Size</div>
            <div class="control-buttons">
                <button class="sidebar-btn btn-resize" onclick="resizeSignature('larger')">+ Larger</button>
                <button class="sidebar-btn btn-resize" onclick="resizeSignature('smaller')">- Smaller</button>
            </div>
        </div>

        <div class="control-section">
            <div class="control-label">Signature Tools</div>
            <div class="control-buttons">
                <button class="sidebar-btn btn-crop" id="cropSignatureBtn" onclick="toggleCropMode()">✂️ Crop</button>
                <button class="sidebar-btn btn-move" id="moveSignatureBtn" onclick="toggleSignatureMoveMode()">🖐️
                    Move</button>
            </div>
        </div>

        <!-- Text Fields Tools -->
        <div class="control-section">
            <div class="control-label">Text Field Editor</div>
            <select class="field-selector" id="fieldSelector" onchange="selectField()">
                <option value="">Select Field...</option>
                <optgroup label="Front Side">
                    <option value="field-lastname">Last Name</option>
                    <option value="field-firstname">First Name</option>
                    <option value="field-middlename">Middle Name</option>
                    <option value="field-birthdate">Birth Date</option>
                    <option value="field-address">Address</option>
                </optgroup>
                <optgroup label="Back Side">
                    <option value="back-field-date-issue">Date Issue</option>
                    <option value="back-field-sex">Sex</option>
                    <option value="back-field-marital-status">Marital Status</option>
                    <option value="back-field-birthplace">Birthplace</option>
                    <option value="back-field-emergency">Emergency Contact</option>
                    <option value="back-field-occupation">Occupation</option>
                    <option value="back-field-special-sector">Special Sector</option>
                </optgroup>
            </select>
        </div>

        <div class="control-section">
            <div class="control-label">Field Font Size</div>
            <div class="font-size-controls">
                <input type="number" class="font-size-input" id="fontSizeInput" min="6" max="72"
                    step="1" placeholder="Size" onchange="applyFontSize()">
                <select class="font-unit-select" id="fontUnit" onchange="applyFontSize()">
                    <option value="px">px</option>
                    <option value="pt">pt</option>
                    <option value="in">in</option>
                </select>
            </div>
            <div class="control-buttons" style="margin-top: 8px;">
                <button class="sidebar-btn btn-resize" onclick="resizeField('larger')">+ Larger</button>
                <button class="sidebar-btn btn-resize" onclick="resizeField('smaller')">- Smaller</button>
            </div>
        </div>

        <div class="control-section">
            <div class="control-label">Field Position</div>
            <div class="control-buttons">
                <button class="sidebar-btn btn-move" id="moveFieldBtn" onclick="toggleFieldMoveMode()">🖐️
                    Move</button>
            </div>
        </div>

        <!-- Reset -->
        <div class="control-section">
            <button class="sidebar-btn btn-reset" onclick="resetAll()">↺ Reset All</button>
        </div>
    </div>

    <div class="card-container">
        <!-- Front Side -->
        <div class="id-card-front" id="idCardFront">
            <!-- Photo -->
            <div class="photo-container">
                @if ($resident->photo_path)
                    <img src="{{ Storage::url($resident->photo_path) }}" alt="{{ $resident->full_name }}">
                @else
                    <div
                        style="width: 100%; height: 100%; background-color: #e5e7eb; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #9ca3af;">
                        No Photo
                    </div>
                @endif
            </div>

            <!-- ID Number -->
            <div class="id-number">{{ $resident->resident_id }}</div>

            <!-- Signature -->
            <div class="signature-container" id="signatureContainer" style="{{ $signatureStyle }}">
                @if ($signaturePath)
                    <img id="signatureImage" src="{{ $signaturePath }}" alt="Signature" style="{{ $imageStyle }}">
                @endif
            </div>

            <!-- Data Fields -->
            <div class="data-field field-lastname" id="field-lastname">{{ strtoupper($resident->last_name) }}</div>
            <div class="data-field field-firstname" id="field-firstname">{{ strtoupper($resident->first_name) }}
                {{ $resident->suffix ? strtoupper($resident->suffix) : '' }}
            </div>
            <div class="data-field field-middlename" id="field-middlename">
                {{ strtoupper($resident->middle_name ?? '') }}</div>
            <div class="data-field field-birthdate" id="field-birthdate">
                {{ $resident->birth_date ? $resident->birth_date->format('F d, Y') : 'N/A' }}
            </div>
            <div class="data-field field-address" id="field-address">
                {{ $resident->household ? $resident->household->address . ', ' . $resident->household->barangay . ', ' . $resident->household->city_municipality : 'N/A' }}
            </div>
        </div>

        <!-- Back Side -->
        <div class="id-card-back" id="idCardBack">
            <!-- Logos -->
            <div class="back-logos">
                <div class="back-logo">
                    <img src="{{ $current_logo_url }}" alt="Municipal Logo">
                </div>
                <div class="back-logo">
                    <img src="{{ $current_favicon_url }}" alt="One Alicia Logo">
                </div>
            </div>

            <!-- Date Issue -->
            <div class="back-field-container field-date-issue-container">
                <div class="back-field-label">Araw ng pagkakaloob/Date issue:</div>
                <div class="back-data-field" id="back-field-date-issue">
                    {{ $resident->date_issue ? $resident->date_issue->format('M d, Y') : now()->format('M d, Y') }}
                </div>
            </div>

            <!-- Sex -->
            <div class="back-field-container field-sex-container">
                <div class="back-field-label">Kasarian/Sex:</div>
                <div class="back-data-field" id="back-field-sex">{{ strtoupper($resident->gender) }}</div>
            </div>

            <!-- Marital Status -->
            <div class="back-field-container field-marital-status-container">
                <div class="back-field-label">Kalagayang Sibil/Marital Status:</div>
                <div class="back-data-field" id="back-field-marital-status">{{ strtoupper($resident->civil_status) }}
                </div>
            </div>

            <!-- Birthplace -->
            <div class="back-field-container field-birthplace-container">
                <div class="back-field-label">Lugar ng Kapanganakan/Place of Birth:</div>
                <div class="back-data-field" id="back-field-birthplace">
                    {{ strtoupper($resident->birthplace ?: 'N/A') }}</div>
            </div>

            <!-- Emergency Contact -->
            <div class="back-field-container field-emergency-container">
                <div class="back-field-label">Emergency Contact Person & No.</div>
                <div class="back-data-field" id="back-field-emergency">
                    {{ strtoupper($resident->emergency_contact ?: 'N/A') }}</div>
            </div>

            <!-- Occupation -->
            <div class="back-field-container field-occupation-container">
                <div class="back-field-label">Occupation:</div>
                <div class="back-data-field" id="back-field-occupation">
                    {{ strtoupper($resident->occupation ?: 'N/A') }}</div>
            </div>

            <!-- Special Sector -->
            <div class="back-field-container field-special-sector-container">
                <div class="back-field-label">Special Sector</div>
                <div class="back-data-field" id="back-field-special-sector">
                    {{ strtoupper($resident->special_sector ?: 'NONE') }}</div>
            </div>

            <!-- QR Code -->
            <div class="qr-code-back">
                <img src="{{ route('qrcode.resident', $resident->id) }}" alt="QR Code">
            </div>

            <!-- Mayor Signature Section -->
            <div class="mayor-signature-section">
                <div class="mayor-signature">
                    <img src="{{ asset('storage/images/mayor-signature.png') }}" alt="Mayor Signature">
                </div>
                <div class="mayor-name">ATTY. JOEL AMOS P. ALEJANDRO, CPA</div>
                <div class="mayor-title">Municipal Mayor</div>
            </div>
        </div>
    </div>

    <script>
        // State management
        let editorState = {
            signature: {
                rotation: 0,
                flipH: false,
                flipV: false,
                scale: 1.0,
                moveMode: false,
                cropMode: false,
                originalSrc: null
            },
            field: {
                selected: null,
                moveMode: false,
                originalFontSizes: {}
            },
            crop: {
                isDrawing: false,
                startX: 0,
                startY: 0,
                endX: 0,
                endY: 0,
                canvas: null,
                ctx: null,
                originalImage: null
            }
        };

        // Store original font sizes for all editable fields
        document.querySelectorAll('.data-field, .back-data-field').forEach(field => {
            const computedStyle = window.getComputedStyle(field);
            editorState.field.originalFontSizes[field.id] = parseFloat(computedStyle.fontSize);
        });

        // Store original signature source
        const signatureImg = document.getElementById('signatureImage');
        if (signatureImg) {
            editorState.signature.originalSrc = signatureImg.src;
        }

        // ===== SIDEBAR DRAGGING =====
        let sidebarDragging = false;
        let sidebarOffset = {
            x: 0,
            y: 0
        };

        const sidebar = document.getElementById('floatingSidebar');
        const sidebarHeader = sidebar.querySelector('.sidebar-header');

        sidebarHeader.addEventListener('mousedown', (e) => {
            sidebarDragging = true;
            sidebarOffset.x = e.clientX - sidebar.offsetLeft;
            sidebarOffset.y = e.clientY - sidebar.offsetTop;
            sidebar.style.cursor = 'grabbing';
        });

        document.addEventListener('mousemove', (e) => {
            if (sidebarDragging) {
                const newLeft = e.clientX - sidebarOffset.x;
                const newTop = e.clientY - sidebarOffset.y;

                const maxLeft = window.innerWidth - sidebar.offsetWidth;
                const maxTop = window.innerHeight - sidebar.offsetHeight;

                sidebar.style.left = Math.max(0, Math.min(newLeft, maxLeft)) + 'px';
                sidebar.style.top = Math.max(0, Math.min(newTop, maxTop)) + 'px';
                sidebar.style.right = 'auto';
            }
        });

        document.addEventListener('mouseup', () => {
            if (sidebarDragging) {
                sidebarDragging = false;
                sidebar.style.cursor = 'move';
            }
        });

        // ===== SIGNATURE TRANSFORM =====
        function applySignatureTransform() {
            const signatureImg = document.getElementById('signatureImage');
            if (!signatureImg) return;

            let transform = '';

            if (editorState.signature.rotation !== 0) {
                transform += `rotate(${editorState.signature.rotation}deg) `;
            }

            let scaleX = editorState.signature.scale * (editorState.signature.flipH ? -1 : 1);
            let scaleY = editorState.signature.scale * (editorState.signature.flipV ? -1 : 1);
            transform += `scale(${scaleX}, ${scaleY})`;

            signatureImg.style.transform = transform;
        }

        function rotateSignature() {
            editorState.signature.rotation = (editorState.signature.rotation + 90) % 360;
            applySignatureTransform();
        }

        function flipHorizontal() {
            editorState.signature.flipH = !editorState.signature.flipH;
            applySignatureTransform();
        }

        function flipVertical() {
            editorState.signature.flipV = !editorState.signature.flipV;
            applySignatureTransform();
        }

        function resizeSignature(direction) {
            if (direction === 'larger') {
                editorState.signature.scale = Math.min(editorState.signature.scale + 0.1, 2.0);
            } else if (direction === 'smaller') {
                editorState.signature.scale = Math.max(editorState.signature.scale - 0.1, 0.5);
            }
            applySignatureTransform();
        }

        // ===== SIGNATURE CROPPING =====
        function toggleCropMode() {
            const signatureImg = document.getElementById('signatureImage');
            if (!signatureImg || !signatureImg.src) {
                alert('No signature image to crop');
                return;
            }

            editorState.signature.cropMode = !editorState.signature.cropMode;
            const cropBtn = document.getElementById('cropSignatureBtn');
            const cropOverlay = document.getElementById('cropOverlay');

            if (editorState.signature.cropMode) {
                cropBtn.classList.add('active');
                initializeCropCanvas();
                cropOverlay.classList.add('active');
            } else {
                cropBtn.classList.remove('active');
                cropOverlay.classList.remove('active');
            }
        }

        function initializeCropCanvas() {
            const signatureImg = document.getElementById('signatureImage');
            const canvas = document.getElementById('cropCanvas');
            const ctx = canvas.getContext('2d');
            const cropSelection = document.getElementById('cropSelection');

            // Load the original (non-transformed) image
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function() {
                // Set canvas size to image size
                canvas.width = img.width;
                canvas.height = img.height;

                // Draw image on canvas
                ctx.drawImage(img, 0, 0);

                // Store in state
                editorState.crop.canvas = canvas;
                editorState.crop.ctx = ctx;
                editorState.crop.originalImage = img;

                // Reset selection
                cropSelection.style.display = 'none';
                editorState.crop.isDrawing = false;
                editorState.crop.startX = 0;
                editorState.crop.startY = 0;
                editorState.crop.endX = 0;
                editorState.crop.endY = 0;
            };
            img.src = signatureImg.src;

            // Setup crop selection drawing
            canvas.onmousedown = startCropSelection;
            canvas.onmousemove = updateCropSelection;
            canvas.onmouseup = endCropSelection;
        }

        function startCropSelection(e) {
            const canvas = editorState.crop.canvas;
            const rect = canvas.getBoundingClientRect();

            editorState.crop.isDrawing = true;
            editorState.crop.startX = e.clientX - rect.left;
            editorState.crop.startY = e.clientY - rect.top;

            const cropSelection = document.getElementById('cropSelection');
            cropSelection.style.display = 'block';
            cropSelection.style.left = editorState.crop.startX + 'px';
            cropSelection.style.top = editorState.crop.startY + 'px';
            cropSelection.style.width = '0px';
            cropSelection.style.height = '0px';
        }

        function updateCropSelection(e) {
            if (!editorState.crop.isDrawing) return;

            const canvas = editorState.crop.canvas;
            const rect = canvas.getBoundingClientRect();
            const cropSelection = document.getElementById('cropSelection');

            editorState.crop.endX = e.clientX - rect.left;
            editorState.crop.endY = e.clientY - rect.top;

            const width = editorState.crop.endX - editorState.crop.startX;
            const height = editorState.crop.endY - editorState.crop.startY;

            if (width < 0) {
                cropSelection.style.left = editorState.crop.endX + 'px';
                cropSelection.style.width = Math.abs(width) + 'px';
            } else {
                cropSelection.style.width = width + 'px';
            }

            if (height < 0) {
                cropSelection.style.top = editorState.crop.endY + 'px';
                cropSelection.style.height = Math.abs(height) + 'px';
            } else {
                cropSelection.style.height = height + 'px';
            }
        }

        function endCropSelection(e) {
            editorState.crop.isDrawing = false;
        }

        function applyCrop() {
            if (!editorState.crop.canvas || !editorState.crop.originalImage) {
                alert('No crop selection made');
                return;
            }

            const startX = Math.min(editorState.crop.startX, editorState.crop.endX);
            const startY = Math.min(editorState.crop.startY, editorState.crop.endY);
            const width = Math.abs(editorState.crop.endX - editorState.crop.startX);
            const height = Math.abs(editorState.crop.endY - editorState.crop.startY);

            if (width < 10 || height < 10) {
                alert('Crop area too small. Please select a larger area.');
                return;
            }

            // Create new canvas for cropped image
            const croppedCanvas = document.createElement('canvas');
            croppedCanvas.width = width;
            croppedCanvas.height = height;
            const croppedCtx = croppedCanvas.getContext('2d');

            // Draw cropped portion
            croppedCtx.drawImage(
                editorState.crop.originalImage,
                startX, startY, width, height,
                0, 0, width, height
            );

            // Update signature image
            const signatureImg = document.getElementById('signatureImage');
            signatureImg.src = croppedCanvas.toDataURL('image/png');

            // Close crop overlay
            toggleCropMode();
        }

        function cancelCrop() {
            toggleCropMode();
        }

        // ===== SIGNATURE DRAGGING =====
        let signatureDragging = false;
        let signatureDragOffset = {
            x: 0,
            y: 0
        };
        let signatureDragHandlers = null;

        function toggleSignatureMoveMode() {
            editorState.signature.moveMode = !editorState.signature.moveMode;
            const moveBtn = document.getElementById('moveSignatureBtn');
            const signatureContainer = document.getElementById('signatureContainer');

            if (editorState.signature.moveMode) {
                moveBtn.classList.add('active');
                signatureContainer.classList.add('draggable');
                enableSignatureDrag();
            } else {
                moveBtn.classList.remove('active');
                signatureContainer.classList.remove('draggable');
                disableSignatureDrag();
            }
        }

        function enableSignatureDrag() {
            const signatureContainer = document.getElementById('signatureContainer');
            const idCard = document.getElementById('idCardFront');

            const onMouseDown = (e) => {
                if (!editorState.signature.moveMode) return;
                signatureDragging = true;

                const rect = signatureContainer.getBoundingClientRect();
                signatureDragOffset.x = e.clientX - rect.left;
                signatureDragOffset.y = e.clientY - rect.top;

                e.preventDefault();
            };

            const onMouseMove = (e) => {
                if (!signatureDragging || !editorState.signature.moveMode) return;

                const cardRect = idCard.getBoundingClientRect();
                let newLeft = e.clientX - cardRect.left - signatureDragOffset.x;
                let newBottom = cardRect.bottom - e.clientY - (signatureContainer.offsetHeight - signatureDragOffset.y);

                const DPI = 96;
                const leftInches = (newLeft / DPI).toFixed(2);
                const bottomInches = (newBottom / DPI).toFixed(2);

                signatureContainer.style.left = leftInches + 'in';
                signatureContainer.style.bottom = bottomInches + 'in';

                e.preventDefault();
            };

            const onMouseUp = () => {
                signatureDragging = false;
            };

            signatureDragHandlers = {
                onMouseDown,
                onMouseMove,
                onMouseUp
            };

            signatureContainer.addEventListener('mousedown', onMouseDown);
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        }

        function disableSignatureDrag() {
            const signatureContainer = document.getElementById('signatureContainer');

            if (signatureDragHandlers) {
                signatureContainer.removeEventListener('mousedown', signatureDragHandlers.onMouseDown);
                document.removeEventListener('mousemove', signatureDragHandlers.onMouseMove);
                document.removeEventListener('mouseup', signatureDragHandlers.onMouseUp);
                signatureDragHandlers = null;
            }
        }

        // ===== FIELD SELECTION =====
        function selectField() {
            const selector = document.getElementById('fieldSelector');
            const selectedValue = selector.value;

            // Remove highlight from all fields
            document.querySelectorAll('.data-field, .back-data-field').forEach(field => {
                field.classList.remove('draggable');
                field.style.outline = '';
                field.style.outlineOffset = '';
            });

            // Disable field move mode if changing selection
            if (editorState.field.moveMode) {
                toggleFieldMoveMode();
            }

            editorState.field.selected = selectedValue;

            if (selectedValue) {
                const field = document.getElementById(selectedValue);
                if (field) {
                    // Update font size input with current value
                    const currentSize = parseFloat(window.getComputedStyle(field).fontSize);
                    document.getElementById('fontSizeInput').value = Math.round(currentSize);

                    // Flash outline
                    field.style.outline = '2px solid #f59e0b';
                    field.style.outlineOffset = '2px';
                    setTimeout(() => {
                        if (field.id === editorState.field.selected) {
                            field.style.outline = '';
                            field.style.outlineOffset = '';
                        }
                    }, 1000);
                }
            } else {
                document.getElementById('fontSizeInput').value = '';
            }
        }

        // ===== FIELD FONT SIZE =====
        function applyFontSize() {
            if (!editorState.field.selected) {
                alert('Please select a field first');
                return;
            }

            const field = document.getElementById(editorState.field.selected);
            if (!field) return;

            const sizeValue = parseFloat(document.getElementById('fontSizeInput').value);
            const unit = document.getElementById('fontUnit').value;

            if (isNaN(sizeValue) || sizeValue <= 0) {
                alert('Please enter a valid font size');
                return;
            }

            field.style.fontSize = sizeValue + unit;
        }

        function resizeField(direction) {
            if (!editorState.field.selected) {
                alert('Please select a field first');
                return;
            }

            const field = document.getElementById(editorState.field.selected);
            if (!field) return;

            const currentSize = parseFloat(window.getComputedStyle(field).fontSize);
            let newSize;

            if (direction === 'larger') {
                newSize = currentSize + 1;
            } else if (direction === 'smaller') {
                newSize = Math.max(currentSize - 1, 6);
            }

            field.style.fontSize = newSize + 'px';
            document.getElementById('fontSizeInput').value = Math.round(newSize);
        }

        // ===== FIELD DRAGGING =====
        let fieldDragging = false;
        let fieldDragOffset = {
            x: 0,
            y: 0
        };
        let fieldDragHandlers = null;

        function toggleFieldMoveMode() {
            if (!editorState.field.selected) {
                alert('Please select a field first');
                return;
            }

            editorState.field.moveMode = !editorState.field.moveMode;
            const moveBtn = document.getElementById('moveFieldBtn');
            const field = document.getElementById(editorState.field.selected);

            if (editorState.field.moveMode) {
                moveBtn.classList.add('active');
                field.classList.add('draggable');
                enableFieldDrag();
            } else {
                moveBtn.classList.remove('active');
                field.classList.remove('draggable');
                disableFieldDrag();
            }
        }

        function enableFieldDrag() {
            const field = document.getElementById(editorState.field.selected);
            const isBackField = field.classList.contains('back-data-field');
            const idCard = isBackField ? document.getElementById('idCardBack') : document.getElementById('idCardFront');

            const onMouseDown = (e) => {
                if (!editorState.field.moveMode) return;
                fieldDragging = true;

                const rect = field.getBoundingClientRect();
                fieldDragOffset.x = e.clientX - rect.left;
                fieldDragOffset.y = e.clientY - rect.top;

                e.preventDefault();
            };

            const onMouseMove = (e) => {
                if (!fieldDragging || !editorState.field.moveMode) return;

                const cardRect = idCard.getBoundingClientRect();

                if (isBackField) {
                    // For back fields, keep parent container positioning
                    const parent = field.parentElement;
                    let newLeft = e.clientX - cardRect.left - fieldDragOffset.x;
                    let newTop = e.clientY - cardRect.top - fieldDragOffset.y;

                    const DPI = 96;
                    const leftInches = (newLeft / DPI).toFixed(2);
                    const topInches = (newTop / DPI).toFixed(2);

                    parent.style.left = leftInches + 'in';
                    parent.style.top = topInches + 'in';
                } else {
                    // For front fields
                    let newLeft = e.clientX - cardRect.left - fieldDragOffset.x;
                    let newTop = e.clientY - cardRect.top - fieldDragOffset.y;

                    const DPI = 96;
                    const leftInches = (newLeft / DPI).toFixed(2);
                    const topInches = (newTop / DPI).toFixed(2);

                    field.style.left = leftInches + 'in';
                    field.style.top = topInches + 'in';
                    field.style.bottom = 'auto';
                }

                e.preventDefault();
            };

            const onMouseUp = () => {
                fieldDragging = false;
            };

            fieldDragHandlers = {
                onMouseDown,
                onMouseMove,
                onMouseUp
            };

            field.addEventListener('mousedown', onMouseDown);
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        }

        function disableFieldDrag() {
            const field = document.getElementById(editorState.field.selected);

            if (fieldDragHandlers) {
                field.removeEventListener('mousedown', fieldDragHandlers.onMouseDown);
                document.removeEventListener('mousemove', fieldDragHandlers.onMouseMove);
                document.removeEventListener('mouseup', fieldDragHandlers.onMouseUp);
                fieldDragHandlers = null;
            }
        }

        // ===== RESET ALL =====
        function resetAll() {
            // Reset signature
            const signatureImg = document.getElementById('signatureImage');
            if (signatureImg && editorState.signature.originalSrc) {
                signatureImg.src = editorState.signature.originalSrc;
            }

            editorState.signature = {
                rotation: 0,
                flipH: false,
                flipV: false,
                scale: 1.0,
                moveMode: false,
                cropMode: false,
                originalSrc: editorState.signature.originalSrc
            };

            const moveSignatureBtn = document.getElementById('moveSignatureBtn');
            const cropSignatureBtn = document.getElementById('cropSignatureBtn');
            const signatureContainer = document.getElementById('signatureContainer');

            moveSignatureBtn.classList.remove('active');
            cropSignatureBtn.classList.remove('active');
            signatureContainer.classList.remove('draggable', 'cropping');
            disableSignatureDrag();

            signatureContainer.style.left = '';
            signatureContainer.style.bottom = '';

            applySignatureTransform();

            // Reset all fields (front and back)
            document.querySelectorAll('.data-field, .back-data-field').forEach(field => {
                field.style.fontSize = '';
                field.style.left = '';
                field.style.top = '';
                field.style.bottom = '';
                field.classList.remove('draggable');
                field.style.outline = '';
                field.style.outlineOffset = '';
            });

            // Reset back field containers
            document.querySelectorAll('.back-field-container').forEach(container => {
                container.style.left = '';
                container.style.top = '';
            });

            // Reset field state
            editorState.field = {
                selected: null,
                moveMode: false,
                originalFontSizes: editorState.field.originalFontSizes
            };

            const fieldSelector = document.getElementById('fieldSelector');
            fieldSelector.value = '';

            document.getElementById('fontSizeInput').value = '';

            const moveFieldBtn = document.getElementById('moveFieldBtn');
            moveFieldBtn.classList.remove('active');
            disableFieldDrag();

            // Close crop overlay if open
            document.getElementById('cropOverlay').classList.remove('active');
        }

        // Delete temporary signature file after page loads
        window.onload = function() {
            setTimeout(function() {
                @if (isset($filename))
                    fetch('{{ route('delete.temp.signature') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            filename: '{{ $filename ?? '' }}'
                        })
                    });
                @endif
            }, 3000);
        };
    </script>
</body>

</html>
