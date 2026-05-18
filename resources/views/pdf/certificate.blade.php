<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Internship</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: #f4f6f9;
            color: #1a2340;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            position: relative;
            overflow: hidden;
        }

        /* ── Decorative corner borders ── */
        .corner {
            position: absolute;
            width: 60px;
            height: 60px;
        }
        .corner-tl { top: 18px; left: 18px; border-top: 4px solid #1a3066; border-left: 4px solid #1a3066; }
        .corner-tr { top: 18px; right: 18px; border-top: 4px solid #1a3066; border-right: 4px solid #1a3066; }
        .corner-bl { bottom: 18px; left: 18px; border-bottom: 4px solid #1a3066; border-left: 4px solid #1a3066; }
        .corner-br { bottom: 18px; right: 18px; border-bottom: 4px solid #1a3066; border-right: 4px solid #1a3066; }

        /* Outer frame */
        .outer-border {
            position: absolute;
            top: 10px; left: 10px; right: 10px; bottom: 10px;
            border: 1.5px solid #b0bccc;
        }
        .inner-border {
            position: absolute;
            top: 16px; left: 16px; right: 16px; bottom: 16px;
            border: 3.5px solid #1a3066;
        }

        .content-wrap {
            position: relative;
            padding: 52px 64px 48px;
            z-index: 10;
        }

        /* ── Header ── */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #1a3066;
            padding-bottom: 18px;
            margin-bottom: 32px;
        }

        .institution-block {
            text-align: left;
        }

        .institution-name {
            font-size: 13px;
            font-weight: bold;
            color: #1a3066;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.5;
        }

        .institution-sub {
            font-size: 10px;
            color: #5a6a80;
            margin-top: 2px;
        }

        .logo-placeholder {
            width: 70px;
            height: 70px;
            border: 1.5px solid #b0bccc;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #b0bccc;
            font-size: 9px;
            text-align: center;
        }

        /* ── Certificate Title ── */
        .cert-label {
            text-align: center;
            margin-bottom: 8px;
        }

        .cert-label-text {
            display: inline-block;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #8a9ab5;
            text-transform: uppercase;
            border-top: 1px solid #c8d2df;
            border-bottom: 1px solid #c8d2df;
            padding: 5px 20px;
        }

        .cert-title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #1a3066;
            letter-spacing: 1px;
            margin: 12px 0 6px;
            text-transform: uppercase;
        }

        .cert-subtitle {
            text-align: center;
            font-size: 11px;
            color: #8a9ab5;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 36px;
        }

        /* ── Divider ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }
        .divider-line {
            flex: 1;
            height: 1px;
            background: #c8d2df;
        }
        .divider-diamond {
            width: 8px;
            height: 8px;
            background: #1a3066;
            transform: rotate(45deg);
        }

        /* ── Body Text ── */
        .certify-text {
            text-align: center;
            font-size: 12px;
            color: #4a5568;
            line-height: 1.8;
            margin-bottom: 14px;
        }

        .student-name {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #1a3066;
            border-bottom: 2px solid #1a3066;
            display: inline-block;
            padding: 0 40px 6px;
            margin: 4px auto 4px;
        }

        .name-wrap {
            text-align: center;
            margin: 6px 0 18px;
        }

        .company-name {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #1a3066;
            margin: 6px 0;
        }

        /* ── Info Grid ── */
        .info-grid {
            display: table;
            width: 100%;
            margin: 28px 0 24px;
            border-collapse: collapse;
        }

        .info-row {
            display: table-row;
        }

        .info-box {
            display: table-cell;
            background: #f7f9fc;
            border: 1px solid #dce4ef;
            padding: 14px 20px;
            text-align: center;
            width: 33.33%;
        }

        .info-box-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #8a9ab5;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .info-box-value {
            font-size: 12px;
            font-weight: bold;
            color: #1a2340;
            line-height: 1.4;
        }

        /* ── Signatures ── */
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 44px;
            border-collapse: collapse;
        }

        .sig-cell {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 0 10px;
            vertical-align: bottom;
        }

        .sig-stamp {
            width: 90px;
            height: 90px;
            border: 2px dashed #b0bccc;
            border-radius: 50%;
            margin: 0 auto 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #b0bccc;
            text-align: center;
        }

        .sig-line {
            border-top: 1.5px solid #1a3066;
            margin: 8px auto 4px;
            width: 80%;
        }

        .sig-name {
            font-size: 10px;
            font-weight: bold;
            color: #1a2340;
        }

        .sig-title {
            font-size: 9px;
            color: #8a9ab5;
            margin-top: 2px;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1.5px solid #1a3066;
            margin-top: 38px;
            padding-top: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-left {
            font-size: 8.5px;
            color: #8a9ab5;
        }

        .footer-ref {
            font-size: 8px;
            color: #b0bccc;
            text-align: right;
        }

        .accent-bar {
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, #1a3066 0%, #2d5db5 50%, #1a3066 100%);
        }

        .accent-bar-bottom {
            position: absolute;
            bottom: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, #1a3066 0%, #2d5db5 50%, #1a3066 100%);
        }

        @media print {
            body { background: white; }
            .page { margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
<div class="page">

    <div class="accent-bar"></div>
    <div class="accent-bar-bottom"></div>

    <div class="outer-border"></div>
    <div class="inner-border"></div>

    <div class="corner corner-tl"></div>
    <div class="corner corner-tr"></div>
    <div class="corner corner-bl"></div>
    <div class="corner corner-br"></div>

    <div class="content-wrap">

        <!-- Header -->
        <div class="header">
            <div class="institution-block">
                <div class="institution-name">University / Institution Name</div>
                <div class="institution-sub">Faculty of Sciences &amp; Technology</div>
                <div class="institution-sub">Department of Computer Science</div>
            </div>
            <div class="logo-placeholder">LOGO</div>
        </div>

        <!-- Title Block -->
        <div class="cert-label">
            <span class="cert-label-text">Official Document</span>
        </div>
        <div class="cert-title">Certificate of Internship</div>
        <div class="cert-subtitle">Attestation de Stage</div>

        <div class="divider">
            <div class="divider-line"></div>
            <div class="divider-diamond"></div>
            <div class="divider-line"></div>
        </div>

        <!-- Body -->
        <p class="certify-text">
            The undersigned authorities hereby certify that the following student
        </p>

        <div class="name-wrap">
            <span class="student-name">{{ optional($internship->student->user)->name }}</span>
        </div>

        <p class="certify-text">
            enrolled at this institution, has successfully completed a professional internship at
        </p>

        <div class="company-name">{{ optional($internship->company)->name }}</div>

        <p class="certify-text">
            This internship was carried out in a satisfactory and professional manner, demonstrating
            dedication, competence, and commitment throughout the entire duration of the program.
        </p>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-row">
                <div class="info-box">
                    <div class="info-box-label">Position / Post</div>
                    <div class="info-box-value">{{ optional($internship->offer)->title }}</div>
                </div>
                <div class="info-box">
                    <div class="info-box-label">Date of Issue</div>
                    <div class="info-box-value">{{ now()->format('d / m / Y') }}</div>
                </div>
                <div class="info-box">
                    <div class="info-box-label">Academic Year</div>
                    <div class="info-box-value">{{ now()->format('Y') }} – {{ now()->addYear()->format('Y') }}</div>
                </div>
            </div>
        </div>

        <p class="certify-text" style="font-style: italic; font-size: 11px; color: #6a7a90; margin-top: 10px;">
            This certificate is issued upon request of the student concerned and may serve as official
            proof of completion of the internship period for academic and professional purposes.
        </p>

        <!-- Signatures -->
        <div class="signature-section">
            <div class="sig-cell">
                <div class="sig-stamp">Official<br>Stamp</div>
                <div class="sig-line"></div>
                <div class="sig-name">Academic Supervisor</div>
                <div class="sig-title">University Representative</div>
            </div>
            <div class="sig-cell">
                <div class="sig-stamp">Official<br>Stamp</div>
                <div class="sig-line"></div>
                <div class="sig-name">Department Head</div>
                <div class="sig-title">Faculty Authority</div>
            </div>
            <div class="sig-cell">
                <div class="sig-stamp">Company<br>Seal</div>
                <div class="sig-line"></div>
                <div class="sig-name">Company Supervisor</div>
                <div class="sig-title">{{ optional($internship->company)->name }}</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-left">
                Document issued on {{ now()->format('d F Y') }} &nbsp;|&nbsp; Internship Management Platform
            </div>
            <div class="footer-ref">
                Ref: CERT-{{ now()->format('Y') }}-{{ str_pad($internship->id ?? '0000', 4, '0', STR_PAD_LEFT) }}
            </div>
        </div>

    </div><!-- .content-wrap -->
</div><!-- .page -->
</body>
</html>