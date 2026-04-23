<!-- resources/views/residents/id-card-portrait.blade.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident ID Card - {{ $resident->full_name }}</title>
    <style>
        @page {
            size: 2.125in 3.375in;
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .print-controls {
            margin-bottom: 20px;
            text-align: center;
        }

        .print-btn {
            background-color: #2563eb;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s;
        }

        .print-btn:hover {
            background-color: #1d4ed8;
        }

        .back-btn {
            background-color: #4b5563;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            margin-left: 10px;
            transition: background-color 0.2s;
        }

        .back-btn:hover {
            background-color: #374151;
        }

        .card-container {
            display: flex;
            flex-direction: column;
            gap: 40px;
        }

        /* ID Card Front Side */
        .id-card-front {
            width: 2.125in;
            height: 3.375in;
            background: linear-gradient(135deg, #4338ca, #6d28d9, #7e22ce);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
            position: relative;
            color: white;
        }

        /* Header with Logo and Title */
        .header {
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 0.1in;
            backdrop-filter: blur(5px);
            height: 0.8in;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 0.05in;
            margin-bottom: 0.05in;
        }

        .logo {
            width: 0.3in;
            height: 0.3in;
            background-color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .header-text {
            text-align: center;
        }

        .header-title {
            font-size: 0.12in;
            font-weight: bold;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .header-subtitle {
            font-size: 0.1in;
            opacity: 0.85;
            line-height: 1.2;
        }

        .id-type {
            width: 100%;
            text-align: center;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 0.04in 0;
            font-weight: 600;
            font-size: 0.11in;
            text-transform: uppercase;
            margin-top: 0.1in;
        }

        /* Card Content */
        .card-content {
            padding: 0.1in;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Photo Section */
        .photo-section {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 0.15in;
        }

        .photo-container {
            width: 1.15in;
            height: 1.35in;
            background-color: white;
            border-radius: 6px;
            overflow: hidden;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .id-number {
            font-size: 0.1in;
            margin-top: 0.05in;
            font-weight: 600;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 0.02in 0.05in;
            border-radius: 3px;
            text-align: center;
            width: 80%;
        }

        /* Info Section */
        .info-section {
            width: 100%;
            font-size: 0.11in;
        }

        .info-row {
            margin-bottom: 0.08in;
        }

        .info-label {
            font-size: 0.08in;
            text-transform: uppercase;
            opacity: 0.7;
            margin-bottom: 0.01in;
        }

        .info-value {
            font-weight: 600;
            font-size: 0.11in;
            line-height: 1.2;
        }

        .info-value.name {
            font-size: 0.13in;
            text-transform: uppercase;
        }

        .info-value.address {
            font-size: 0.1in;
        }

        /* Background Pattern */
        .background-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0.05;
            z-index: 0;
            pointer-events: none;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><polygon points="50,15 85,85 15,85" stroke="white" stroke-width="2" fill="none"/></svg>');
            background-size: 1in 1in;
        }

        /* ID Card Back Side */
        .id-card-back {
            width: 2.125in;
            height: 3.375in;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
            position: relative;
            color: #1f2937;
        }

        .back-header {
            height: 0.3in;
            background: linear-gradient(90deg, #4338ca, #7e22ce);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.11in;
            text-transform: uppercase;
            letter-spacing: 0.02in;
        }

        .back-content {
            padding: 0.15in 0.1in;
        }

        .back-info {
            margin-bottom: 0.15in;
        }

        .back-row {
            margin-bottom: 0.08in;
            display: flex;
            font-size: 0.09in;
        }

        .back-label {
            width: 0.8in;
            color: #6b7280;
            font-size: 0.08in;
        }

        .back-value {
            flex: 1;
            font-weight: 600;
        }

        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 0.1in;
        }

        .qr-code {
            width: 1.2in;
            height: 1.2in;
            background-color: white;
            padding: 0.05in;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }

        .qr-code img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .signature-container {
            margin-top: 0.1in;
            border-bottom: 1px solid #9ca3af;
            width: 1.5in;
            height: 0.3in;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            margin-left: auto;
            margin-right: auto;
        }

        .signature-container img {
            max-width: 100%;
            max-height: 0.25in;
            object-fit: contain;
        }

        .official-signature {
            position: absolute;
            bottom: 0.15in;
            width: 100%;
            text-align: center;
        }

        .official-name {
            font-size: 0.08in;
            font-weight: 600;
            text-transform: uppercase;
        }

        .official-title {
            font-size: 0.07in;
            color: #6b7280;
        }

        .footer-logos {
            position: absolute;
            bottom: 0.1in;
            left: 0.1in;
            display: flex;
            gap: 0.05in;
        }

        .footer-logo {
            width: 0.25in;
            height: 0.25in;
            background-color: #f3f4f6;
            border-radius: 4px;
            padding: 0.03in;
        }

        .footer-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* For printing */
        @media print {
            body {
                background: none;
                padding: 0;
                margin: 0;
            }

            .print-controls {
                display: none;
            }

            .card-container {
                gap: 0;
            }

            .id-card-front {
                margin-bottom: 0;
                page-break-after: always;
                border-radius: 0;
                box-shadow: none;
            }

            .id-card-back {
                page-break-before: always;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="print-controls">
        <button class="print-btn" onclick="window.print()">Print ID Card</button>
        <a href="{{ route('residents.show', $resident->id) }}" class="back-btn">Back to Resident</a>
    </div>

    <div class="card-container">
        <!-- Front Side -->
        <div class="id-card-front">
            <!-- Header -->
            <div class="header">
                <div class="logo-container">
                    <div class="logo">
                        <img src="{{ asset('images/municipal-logo.png') }}" alt="Municipal Logo">
                    </div>
                </div>
                <div class="header-text">
                    <div class="header-title">Republic of the Philippines</div>
                    <div class="header-subtitle">Municipality of Alicia</div>
                    <div class="header-subtitle">Province of Isabela</div>
                </div>
                <div class="id-type">Resident Identification Card</div>
            </div>

            <!-- Content -->
            <div class="card-content">
                <!-- Photo Section -->
                <div class="photo-section">
                    <div class="photo-container">
                        @if ($resident->photo_path)
                            <img src="{{ Storage::url($resident->photo_path) }}" alt="{{ $resident->full_name }}">
                        @else
                            <div
                                style="width: 100%; height: 100%; background-color: #eee; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #999;">
                                No Photo</div>
                        @endif
                    </div>
                    <div class="id-number">ID NO: {{ $resident->resident_id }}</div>
                </div>

                <!-- Info Section -->
                <div class="info-section">
                    <div class="info-row">
                        <div class="info-label">Last Name</div>
                        <div class="info-value name">{{ strtoupper($resident->last_name) }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">First Name</div>
                        <div class="info-value name">{{ strtoupper($resident->first_name) }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Middle Name</div>
                        <div class="info-value">
                            {{ $resident->middle_name ? strtoupper($resident->middle_name) : 'N/A' }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value">
                            {{ $resident->birth_date ? $resident->birth_date->format('M. d, Y') : 'N/A' }}</div>
                    </div>

                    <div class="info-row">
                        <div class="info-label">Address</div>
                        <div class="info-value address">
                            {{ $resident->household ? $resident->household->barangay . ', ' . $resident->household->city_municipality . ', ' . $resident->household->province : 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Background Pattern -->
            <div class="background-pattern"></div>
        </div>

        <!-- Back Side -->
        <div class="id-card-back">
            <!-- Header -->
            <div class="back-header">
                Important Information
            </div>

            <!-- Content -->
            <div class="back-content">
                <!-- Info Section -->
                <div class="back-info">
                    <div class="back-row">
                        <div class="back-label">Date Issued</div>
                        <div class="back-value">
                            {{ $resident->date_issue ? $resident->date_issue->format('M d, Y') : now()->format('M d, Y') }}
                        </div>
                    </div>

                    <div class="back-row">
                        <div class="back-label">Sex</div>
                        <div class="back-value">{{ ucfirst($resident->gender) }}</div>
                    </div>

                    <div class="back-row">
                        <div class="back-label">Marital Status</div>
                        <div class="back-value">{{ ucfirst($resident->civil_status) }}</div>
                    </div>

                    <div class="back-row">
                        <div class="back-label">Birthplace</div>
                        <div class="back-value">{{ $resident->birthplace ?: 'N/A' }}</div>
                    </div>

                    <div class="back-row">
                        <div class="back-label">Emergency</div>
                        <div class="back-value">{{ $resident->emergency_contact ?: 'N/A' }}</div>
                    </div>

                    <div class="back-row">
                        <div class="back-label">Contact No.</div>
                        <div class="back-value">{{ $resident->contact_number ?: 'N/A' }}</div>
                    </div>

                    <div class="back-row">
                        <div class="back-label">Occupation</div>
                        <div class="back-value">{{ $resident->occupation ?: 'N/A' }}</div>
                    </div>

                    <div class="back-row">
                        <div class="back-label">Special Sector</div>
                        <div class="back-value">{{ $resident->special_sector ?: 'N/A' }}</div>
                    </div>
                </div>

                <!-- QR Section -->
                <div class="qr-section">
                    <div class="qr-code">
                        <img src="{{ route('qrcode.resident', $resident->id) }}" alt="QR Code">
                    </div>

                    <!-- Signature -->
                    <div class="signature-container">
                        @if ($resident->signature)
                            <img src="{{ str_starts_with($resident->signature, 'data:') ? $resident->signature : asset($resident->signature) }}"
                                alt="Signature">
                        @endif
                    </div>
                </div>

                <!-- Official Signature -->
                <div class="official-signature">
                    <div class="official-name">ATTY. JOEL AMOS P. ALEJANDRO, CPA</div>
                    <div class="official-title">Municipal Mayor</div>
                </div>

                <!-- Footer Logos -->
                <div class="footer-logos">
                    <div class="footer-logo">
                        <div class="flex items-center flex-shrink-0">
                            <a href="{{ route('dashboard') }}" class="flex items-center">
                                <span class="text-xl font-bold text-blue-600">OneAlicia</span>
                                <span class="text-xl font-bold text-gray-800">IDPortal</span>
                            </a>
                        </div>
                    </div>
                    <div class="footer-logo">
                        <img src="{{ asset('images/one-alicia-logo.png') }}" alt="Logo 2">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-print when the page loads (optional)
        window.onload = function() {
            // Uncomment the line below if you want the ID card to print automatically
            // window.print();
        };
    </script>
</body>

</html>
