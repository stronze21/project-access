<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident ID Card - {{ $resident->full_name }}</title>
    <style>
        @page {
            size: 3in 2in;
            margin: 0;
        }

        /* Reset and general styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f0f0f0;
            padding: 0;
            margin: 0;
        }

        /* ID Card Container */
        .id-card {
            width: 3in;
            height: 2in;
            background-color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            page-break-after: always;
            border: 1px solid #ddd;
        }

        /* Header */
        .header {
            background-color: #5b2c6f;
            color: white;
            padding: 3px 10px 3px 35px;
            text-align: left;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .header h1 {
            font-size: 7.5px;
            margin: 0;
            font-weight: bold;
            line-height: 1.2;
        }

        .header h2 {
            font-size: 7px;
            margin-top: 1px;
            font-weight: normal;
        }

        .header h3 {
            font-size: 9px;
            margin-top: 2px;
            text-align: center;
            background: transparent;
            padding: 2px 0;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        /* Logo */
        .logo {
            position: absolute;
            top: 2px;
            left: 8px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            padding: 2px;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Card Content */
        .card-content {
            display: flex;
            flex: 1;
            position: relative;
            background-color: #5b2c6f;
            background-image: none;
        }

        /* Yellow accent strip */
        .yellow-strip {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 0.35in;
            background-color: #ffd700;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 8px;
        }

        .municipality-logo {
            width: 30px;
            height: 30px;
            background: white;
            border-radius: 50%;
            padding: 3px;
            margin-bottom: 5px;
        }

        .municipality-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .id-number-vertical {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            font-size: 7px;
            font-weight: bold;
            color: #5b2c6f;
            letter-spacing: 1px;
            margin-top: 10px;
        }

        /* Photo Container */
        .photo-container {
            width: 0.95in;
            padding: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .photo {
            width: 0.8in;
            height: 1in;
            border: 2px solid #fff;
            background-color: #f0f0f0;
            margin-bottom: 3px;
            position: relative;
            overflow: hidden;
        }

        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .id-number {
            font-size: 8px;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            margin-top: 3px;
        }

        /* Details Container */
        .details-container {
            flex: 1;
            padding: 8px 5px 5px 5px;
            color: white;
            max-width: 1.5in;
        }

        .detail-row {
            margin-bottom: 2px;
        }

        .detail-label {
            font-size: 5.5px;
            color: #ffffff;
            opacity: 0.8;
            text-transform: uppercase;
        }

        .detail-value {
            font-size: 10px;
            font-weight: bold;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .detail-value.smaller {
            font-size: 8px;
        }

        /* Background Design */
        .background-design {
            position: absolute;
            bottom: 8px;
            left: 8px;
            width: 1.2in;
            height: 0.6in;
            opacity: 0.15;
        }

        /* Back Side */
        .id-card-back {
            width: 3in;
            height: 2in;
            background-color: white;
            position: relative;
            page-break-before: always;
            border: 1px solid #ddd;
        }

        .back-header {
            background-color: #5b2c6f;
            color: #ffd700;
            padding: 3px 10px;
            text-align: right;
            font-size: 7px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .back-header-left {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .back-header-logo {
            width: 18px;
            height: 18px;
            background: white;
            border-radius: 50%;
            padding: 2px;
        }

        .back-header-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .back-content {
            padding: 8px 10px;
        }

        .back-row {
            margin-bottom: 3px;
            display: flex;
            font-size: 7px;
        }

        .back-label {
            font-size: 7px;
            color: #000;
            width: 1.3in;
            font-weight: normal;
        }

        .back-value {
            font-size: 7px;
            font-weight: bold;
            flex: 1;
            text-transform: uppercase;
        }

        .qr-code {
            position: absolute;
            top: 35px;
            right: 15px;
            width: 0.9in;
            height: 0.9in;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ddd;
        }

        .qr-code img {
            width: 100%;
            height: 100%;
        }

        .signature {
            margin-top: 5px;
            border-bottom: 1px solid #000;
            width: 1.2in;
            height: 0.3in;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .signature img {
            max-width: 100%;
            max-height: 0.3in;
        }

        .issued-by {
            position: absolute;
            bottom: 8px;
            right: 15px;
            text-align: center;
            font-size: 7px;
            width: 0.9in;
        }

        .issued-by .official {
            font-weight: bold;
            font-size: 7px;
            margin-bottom: 2px;
            line-height: 1.1;
        }

        .issued-by .position {
            font-size: 6px;
            color: #666;
        }

        .footer-logos {
            position: absolute;
            bottom: 8px;
            left: 10px;
            display: flex;
            gap: 8px;
        }

        .footer-logo {
            width: 22px;
            height: 22px;
            background-color: transparent;
            border-radius: 50%;
        }

        .footer-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        @media print {
            body {
                background: none;
                padding: 0;
                margin: 0;
            }

            .print-controls {
                display: none;
            }
        }

        .print-controls {
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .print-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }

        .back-btn {
            background-color: #555;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="print-controls">
        <button class="print-btn" onclick="window.print()">Print ID Card</button>
        <a href="{{ route('residents.show', $resident->id) }}" class="back-btn">Back to Resident</a>
    </div>

    <!-- Front Side of ID Card -->
    <div class="id-card">
        <!-- Header -->
        <div class="header">
            <div class="logo">
                <img src="{{ asset('images/municipal-logo.png') }}" alt="Municipal Logo">
            </div>
            <h1>REPUBLIC OF THE PHILIPPINES</h1>
            <h2>PROVINCE OF ISABELA</h2>
            <h2>MUNICIPALITY OF ALICIA</h2>
            <h3>MUNICIPAL IDENTIFICATION SYSTEM</h3>
        </div>

        <!-- Content -->
        <div class="card-content">
            <!-- Photo Section -->
            <div class="photo-container">
                <div class="photo">
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

            <!-- Details Section -->
            <div class="details-container">
                <div class="detail-row">
                    <div class="detail-label">Apelyido/Last Name</div>
                    <div class="detail-value">{{ strtoupper($resident->last_name) }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Mga Pangalan/Given Names</div>
                    <div class="detail-value">{{ strtoupper($resident->first_name) }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Gitnang Apelyido/Middle Name</div>
                    <div class="detail-value">{{ strtoupper($resident->middle_name) }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Petsa ng Kapanganakan/Date of Birth</div>
                    <div class="detail-value smaller">
                        {{ $resident->birth_date ? $resident->birth_date->format('M. d, Y') : 'N/A' }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Tirahan/Address</div>
                    <div class="detail-value smaller">
                        {{ $resident->household ? $resident->household->barangay : 'N/A' }},
                        {{ $resident->household ? $resident->household->city_municipality : 'N/A' }},
                        {{ $resident->household ? $resident->household->province : 'N/A' }}
                    </div>
                </div>

                <!-- Background Design -->
                <img class="background-design" src="{{ asset('images/mountain-outline.png') }}" alt="Background">
            </div>

            <!-- Yellow Strip with Logo and ID -->
            <div class="yellow-strip">
                <div class="municipality-logo">
                    <img src="{{ asset('images/one-alicia-logo.png') }}" alt="One Alicia Logo">
                </div>
                <div class="id-number-vertical">R-{{ $resident->resident_id }}</div>
            </div>
        </div>
    </div>

    <!-- Back Side of ID Card -->
    <div class="id-card-back">
        <div class="back-header">
            <div class="back-header-left">
                <div class="back-header-logo">
                    <img src="{{ asset('images/one-alicia-logo.png') }}" alt="One Alicia Logo">
                </div>
                <span>10-17-2025</span>
            </div>
        </div>

        <div class="back-content">
            <div class="back-row">
                <div class="back-label">Araw ng pagkakaloob/Date issue:</div>
                <div class="back-value">{{ $resident->date_issue ? $resident->date_issue->format('M d, Y') : 'N/A' }}
                </div>
            </div>

            <div class="back-row">
                <div class="back-label">Kasarian/Sex:</div>
                <div class="back-value">{{ ucfirst($resident->gender) }}</div>
            </div>

            <div class="back-row">
                <div class="back-label">Kalagayang Sibil/Marital Status:</div>
                <div class="back-value">{{ ucfirst($resident->civil_status) }}</div>
            </div>

            <div class="back-row">
                <div class="back-label">Lugar ng Kapanganakan/Place of Birth:</div>
                <div class="back-value">{{ $resident->birthplace ?: 'N/A' }}</div>
            </div>

            <div class="back-row">
                <div class="back-label">Emergency Contact Person & No:</div>
                <div class="back-value">{{ $resident->emergency_contact ?: 'N/A' }}</div>
            </div>

            <div class="back-row">
                <div class="back-label">Occupation:</div>
                <div class="back-value">{{ $resident->occupation ?: 'N/A' }}</div>
            </div>

            <div class="back-row">
                <div class="back-label">Eligibility:</div>
                <div class="back-value">{{ $resident->special_sector ?: 'N/A' }}</div>
            </div>

            <div class="back-row">
                <div class="back-label">Signature:</div>
                <div class="signature">
                    @if ($resident->signature)
                        <img src="{{ str_starts_with($resident->signature, 'data:') ? $resident->signature : asset($resident->signature) }}"
                            alt="Signature">
                    @endif
                </div>
            </div>

            <!-- QR Code -->
            <div class="qr-code">
                <img src="{{ route('qrcode.resident', $resident->id) }}" alt="QR Code">
            </div>

            <!-- Issuing Authority -->
            <div class="issued-by">
                <div class="official">ATTY. JOEL AMOS P. ALEJANDRO, CPA</div>
                <div class="position">Municipal Mayor</div>
            </div>

            <!-- Footer Logos -->
            <div class="footer-logos">
                <div class="footer-logo">
                    <img src="{{ asset('images/municipal-logo.png') }}" alt="Municipal Logo"
                        style="width: 100%; height: 100%; object-fit: contain;">
                </div>
                <div class="footer-logo">
                    <img src="{{ asset('images/one-alicia-logo.png') }}" alt="One Alicia Logo"
                        style="width: 100%; height: 100%; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-print when the page loads
        window.onload = function() {
            // Uncomment the line below if you want the ID card to print automatically
            // window.print();
        };
    </script>
</body>

</html>
