<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 12mm 10mm; }

        * { box-sizing: border-box; }

        html, body {
            height: auto;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            color: #1a1a1a;
            background: #faf4e6;
            position: relative;
        }

        /*
         * Watermark must stay inside the flow document (no position:fixed) — DomPDF often
         * emits a blank leading page when fixed layers cover the first page.
         */
        .sheet {
            position: relative;
            z-index: 0;
            overflow: hidden;
            background: linear-gradient(165deg, rgba(255, 253, 248, 0.97) 0%, rgba(255, 249, 235, 0.99) 45%, rgba(252, 246, 232, 1) 100%);
            border: 0;
            padding: 0;
            page-break-inside: avoid;
            /* ~A4 printable height so absolute layers (watermark) stretch to the physical bottom */
            min-height: 280mm;
        }

        .watermark {
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            min-height: 280mm;
            z-index: 0;
            opacity: 0.055;
            background-repeat: repeat;
            background-size: 72px auto;
            background-position: 0 0;
            pointer-events: none;
        }

        /*
         * Guilloche-style frame (REPRONIG burgundy tones) — vector edges, no raster border image.
         */
        .guilloche-edge {
            position: absolute;
            z-index: 2;
            pointer-events: none;
            opacity: 0.88;
        }

        .guilloche-strip-h {
            left: 96px;
            right: 96px;
            height: 26px;
            overflow: hidden;
        }

        .guilloche-strip-h svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .guilloche-strip-t { top: 2px; }
        .guilloche-strip-b { bottom: 2px; }

        .guilloche-strip-v {
            top: 96px;
            bottom: 96px;
            width: 26px;
            overflow: hidden;
        }

        .guilloche-strip-v svg {
            display: block;
            width: 100%;
            height: 100%;
        }

        .guilloche-strip-l { left: 2px; }
        .guilloche-strip-r { right: 2px; }

        .corner-rosette {
            position: absolute;
            width: 104px;
            height: 104px;
            z-index: 2;
            pointer-events: none;
            opacity: 0.9;
        }

        .corner-rosette-tl { top: 0; left: 0; }
        .corner-rosette-tr { top: 0; right: 0; }
        .corner-rosette-bl { bottom: 0; left: 0; }
        .corner-rosette-br { bottom: 0; right: 0; }

        .sheet-inner {
            margin: 34px 32px 34px;
            border: 0;
            padding: 0;
            position: relative;
            z-index: 3;
            background: transparent;
            box-shadow:
                inset 0 0 0 1px rgba(106, 16, 37, 0.22),
                inset 0 0 0 3px rgba(106, 16, 37, 0.06);
        }

        .masthead {
            padding: 28px 28px 14px;
            border-bottom: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.45) 0%, rgba(255, 250, 240, 0.25) 100%);
            position: relative;
            z-index: 3;
        }

        .eyebrow {
            text-align: center;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: #6a1025;
            font-weight: 700;
            margin: 2px 0 0;
        }

        .platform {
            text-align: center;
            font-size: 11pt;
            font-weight: 700;
            color: #2b0a12;
            margin: 6px 0 0;
        }

        .body {
            padding: 20px 32px 24px;
            position: relative;
            z-index: 3;
        }

        .doc-title {
            text-align: center;
            font-family: DejaVu Serif, Georgia, Times New Roman, serif;
            font-size: 18pt;
            font-weight: 700;
            margin: 2px 0 10px;
            color: #2b0a12;
            letter-spacing: 0.06em;
            font-variant: small-caps;
        }

        .ribbon {
            text-align: center;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 9.5pt;
            color: #000000;
            margin: 0 0 18px;
            line-height: 1.5;
            padding: 0 10px;
        }

        .recipient {
            text-align: center;
            font-family: DejaVu Serif, Georgia, Times New Roman, serif;
            font-size: 15pt;
            font-weight: 700;
            margin-bottom: 4px;
            color: #1a0a10;
            letter-spacing: 0.02em;
        }

        .sub-recipient {
            text-align: center;
            font-family: DejaVu Serif, Georgia, Times New Roman, serif;
            font-size: 9.5pt;
            color: #4a3d42;
            margin-bottom: 16px;
        }

        .panel {
            font-family: DejaVu Serif, Georgia, Times New Roman, serif;
            border: 0;
            background: rgba(255, 255, 255, 0.55);
            padding: 8px 14px 8px;
            margin: 0 auto 16px;
            max-width: 100%;
            border-radius: 2px;
            box-shadow: 0 0 0 1px rgba(106, 16, 37, 0.12);
        }

        .panel-table { width: 100%; border-collapse: collapse; }

        .panel-table td {
            padding: 7px 4px;
            vertical-align: top;
            border-bottom: 1px solid rgba(237, 229, 220, 0.95);
        }

        .panel-table tr:last-child td { border-bottom: 0; }

        .label {
            width: 36%;
            font-size: 8.75pt;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6a1025;
            font-weight: 700;
        }

        .value {
            font-size: 10.75pt;
            font-weight: 600;
            color: #1a0a10;
            letter-spacing: 0.015em;
        }

        .authorisation-block {
            margin-top: 40px;
            text-align: center;
            page-break-inside: avoid;
        }

        .auth-signature-only {
            margin: 0 auto 4px;
        }

        .auth-rule {
            width: 260px;
            max-width: 85%;
            height: 0;
            margin: 10px auto 12px;
            border: 0;
            border-top: 1px solid #344054;
            font-size: 0;
            line-height: 0;
        }

        .auth-authorised {
            font-size: 9.5pt;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 10px;
            letter-spacing: 0.02em;
        }

        .auth-name {
            font-size: 10pt;
            font-weight: 700;
            color: #1a1a1a;
            margin: 0 0 2px;
        }

        .auth-role {
            font-size: 9pt;
            color: #344054;
            margin: 0 0 2px;
        }

        .auth-org {
            font-size: 9.5pt;
            font-weight: 700;
            color: #6a1025;
            letter-spacing: 0.06em;
            margin: 0 0 4px;
        }

        .auth-seal-wrap {
            margin-top: 14px;
            text-align: center;
        }

        .auth-seal-wrap img {
            display: inline-block;
            vertical-align: middle;
        }

        .footer {
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid rgba(224, 212, 196, 0.85);
            text-align: center;
            font-size: 7.5pt;
            color: #667085;
            line-height: 1.5;
        }

        .ref {
            font-family: DejaVu Sans Mono, monospace;
            font-size: 7.5pt;
            margin-top: 6px;
            color: #344054;
        }

        .issued-line {
            text-align: center;
            font-size: 8pt;
            color: #667085;
            margin-top: 4px;
        }
    </style>
</head>
<body>
<div class="sheet">
    @if(!empty($watermarkDataUri))
        <div class="watermark" style="background-image:url('{!! $watermarkDataUri !!}');"></div>
    @endif

    {{-- Guilloche horizontal bands --}}
    <div class="guilloche-edge guilloche-strip-h guilloche-strip-t" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 26" preserveAspectRatio="none" width="100%" height="26">
            <g fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="#6a1025" stroke-width="0.7" opacity="0.92" d="M0 17 Q45 5 90 17 T180 17 T270 17 T360 17"/>
                <path stroke="#9b2c24" stroke-width="0.48" opacity="0.78" d="M0 21 Q45 11 90 21 T180 21 T270 21 T360 21"/>
                <path stroke="#6a1025" stroke-width="0.38" opacity="0.55" d="M0 12 Q45 22 90 12 T180 12 T270 12 T360 12"/>
            </g>
            <g fill="#6a1025" opacity="0.42">
                <circle cx="45" cy="5" r="1.25"/><circle cx="135" cy="5" r="1.25"/><circle cx="225" cy="5" r="1.25"/><circle cx="315" cy="5" r="1.25"/>
            </g>
        </svg>
    </div>
    <div class="guilloche-edge guilloche-strip-h guilloche-strip-b" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 26" preserveAspectRatio="none" width="100%" height="26">
            <g transform="translate(0,26) scale(1,-1)">
                <g fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="#6a1025" stroke-width="0.7" opacity="0.92" d="M0 17 Q45 5 90 17 T180 17 T270 17 T360 17"/>
                    <path stroke="#9b2c24" stroke-width="0.48" opacity="0.78" d="M0 21 Q45 11 90 21 T180 21 T270 21 T360 21"/>
                    <path stroke="#6a1025" stroke-width="0.38" opacity="0.55" d="M0 12 Q45 22 90 12 T180 12 T270 12 T360 12"/>
                </g>
                <g fill="#6a1025" opacity="0.42">
                    <circle cx="45" cy="5" r="1.25"/><circle cx="135" cy="5" r="1.25"/><circle cx="225" cy="5" r="1.25"/><circle cx="315" cy="5" r="1.25"/>
                </g>
            </g>
        </svg>
    </div>

    <div class="guilloche-edge guilloche-strip-v guilloche-strip-l" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26 360" preserveAspectRatio="none" width="26" height="100%">
            <g fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="#6a1025" stroke-width="0.7" opacity="0.92" d="M17 0 Q5 45 17 90 T17 180 T17 270 T17 360"/>
                <path stroke="#9b2c24" stroke-width="0.48" opacity="0.78" d="M21 0 Q11 45 21 90 T21 180 T21 270 T21 360"/>
                <path stroke="#6a1025" stroke-width="0.38" opacity="0.55" d="M12 0 Q22 45 12 90 T12 180 T12 270 T12 360"/>
            </g>
            <g fill="#6a1025" opacity="0.42">
                <circle cx="5" cy="45" r="1.25"/><circle cx="5" cy="135" r="1.25"/><circle cx="5" cy="225" r="1.25"/><circle cx="5" cy="315" r="1.25"/>
            </g>
        </svg>
    </div>
    <div class="guilloche-edge guilloche-strip-v guilloche-strip-r" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26 360" preserveAspectRatio="none" width="26" height="100%">
            <g transform="translate(26,0) scale(-1,1)">
                <g fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="#6a1025" stroke-width="0.7" opacity="0.92" d="M17 0 Q5 45 17 90 T17 180 T17 270 T17 360"/>
                    <path stroke="#9b2c24" stroke-width="0.48" opacity="0.78" d="M21 0 Q11 45 21 90 T21 180 T21 270 T21 360"/>
                    <path stroke="#6a1025" stroke-width="0.38" opacity="0.55" d="M12 0 Q22 45 12 90 T12 180 T12 270 T12 360"/>
                </g>
                <g fill="#6a1025" opacity="0.42">
                    <circle cx="5" cy="45" r="1.25"/><circle cx="5" cy="135" r="1.25"/><circle cx="5" cy="225" r="1.25"/><circle cx="5" cy="315" r="1.25"/>
                </g>
            </g>
        </svg>
    </div>

    <svg class="corner-rosette corner-rosette-tl" viewBox="0 0 104 104" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <g fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="#6a1025" stroke-width="0.95" opacity="0.9" d="M0 98 C0 40 40 0 98 0"/>
            <path stroke="#6a1025" stroke-width="0.75" opacity="0.75" d="M0 80 C0 38 38 4 86 4"/>
            <path stroke="#9b2c24" stroke-width="0.55" opacity="0.65" d="M0 62 C0 36 36 10 74 10"/>
            <path stroke="#6a1025" stroke-width="0.5" opacity="0.55" d="M0 46 C0 30 30 14 62 14"/>
            <path stroke="#9b2c24" stroke-width="0.38" opacity="0.5" d="M10 100 C34 72 72 34 100 10"/>
            <path stroke="#6a1025" stroke-width="0.36" opacity="0.48" d="M6 88 C32 60 60 32 92 6"/>
            <path stroke="#6a1025" stroke-width="0.42" opacity="0.62" d="M10 0 Q52 10 102 10 M0 10 Q10 52 10 102"/>
        </g>
        <g fill="#6a1025" stroke="none" opacity="0.5">
            <circle cx="52" cy="16" r="1.15"/><circle cx="74" cy="26" r="1.15"/><circle cx="88" cy="42" r="1.15"/><circle cx="20" cy="74" r="1.15"/><circle cx="34" cy="90" r="1.15"/><circle cx="16" cy="52" r="1.15"/>
        </g>
    </svg>
    <svg class="corner-rosette corner-rosette-tr" viewBox="0 0 104 104" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <g fill="none" stroke-linecap="round" stroke-linejoin="round" transform="translate(104,0) scale(-1,1)">
            <path stroke="#6a1025" stroke-width="0.95" opacity="0.9" d="M0 98 C0 40 40 0 98 0"/>
            <path stroke="#6a1025" stroke-width="0.75" opacity="0.75" d="M0 80 C0 38 38 4 86 4"/>
            <path stroke="#9b2c24" stroke-width="0.55" opacity="0.65" d="M0 62 C0 36 36 10 74 10"/>
            <path stroke="#6a1025" stroke-width="0.5" opacity="0.55" d="M0 46 C0 30 30 14 62 14"/>
            <path stroke="#9b2c24" stroke-width="0.38" opacity="0.5" d="M10 100 C34 72 72 34 100 10"/>
            <path stroke="#6a1025" stroke-width="0.36" opacity="0.48" d="M6 88 C32 60 60 32 92 6"/>
            <path stroke="#6a1025" stroke-width="0.42" opacity="0.62" d="M10 0 Q52 10 102 10 M0 10 Q10 52 10 102"/>
        </g>
    </svg>
    <svg class="corner-rosette corner-rosette-bl" viewBox="0 0 104 104" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <g fill="none" stroke-linecap="round" stroke-linejoin="round" transform="translate(0,104) scale(1,-1)">
            <path stroke="#6a1025" stroke-width="0.95" opacity="0.9" d="M0 98 C0 40 40 0 98 0"/>
            <path stroke="#6a1025" stroke-width="0.75" opacity="0.75" d="M0 80 C0 38 38 4 86 4"/>
            <path stroke="#9b2c24" stroke-width="0.55" opacity="0.65" d="M0 62 C0 36 36 10 74 10"/>
            <path stroke="#6a1025" stroke-width="0.5" opacity="0.55" d="M0 46 C0 30 30 14 62 14"/>
            <path stroke="#9b2c24" stroke-width="0.38" opacity="0.5" d="M10 100 C34 72 72 34 100 10"/>
            <path stroke="#6a1025" stroke-width="0.36" opacity="0.48" d="M6 88 C32 60 60 32 92 6"/>
            <path stroke="#6a1025" stroke-width="0.42" opacity="0.62" d="M10 0 Q52 10 102 10 M0 10 Q10 52 10 102"/>
        </g>
    </svg>
    <svg class="corner-rosette corner-rosette-br" viewBox="0 0 104 104" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <g fill="none" stroke-linecap="round" stroke-linejoin="round" transform="translate(104,104) scale(-1,-1)">
            <path stroke="#6a1025" stroke-width="0.95" opacity="0.9" d="M0 98 C0 40 40 0 98 0"/>
            <path stroke="#6a1025" stroke-width="0.75" opacity="0.75" d="M0 80 C0 38 38 4 86 4"/>
            <path stroke="#9b2c24" stroke-width="0.55" opacity="0.65" d="M0 62 C0 36 36 10 74 10"/>
            <path stroke="#6a1025" stroke-width="0.5" opacity="0.55" d="M0 46 C0 30 30 14 62 14"/>
            <path stroke="#9b2c24" stroke-width="0.38" opacity="0.5" d="M10 100 C34 72 72 34 100 10"/>
            <path stroke="#6a1025" stroke-width="0.36" opacity="0.48" d="M6 88 C32 60 60 32 92 6"/>
            <path stroke="#6a1025" stroke-width="0.42" opacity="0.62" d="M10 0 Q52 10 102 10 M0 10 Q10 52 10 102"/>
        </g>
    </svg>

    <div class="sheet-inner">
        <div class="masthead">
            {!! $brandLogoHtml !!}
            {{-- <div class="eyebrow">Member record</div>
            <div class="platform">{{ $platformName }}</div> --}}
            <div class="issued-line">Document generated {{ $generatedAt }}</div>
        </div>

        <div class="body">
            <div class="doc-title">{{ $documentTitle }}</div>
            <div class="ribbon">{{ $ribbonLine1 }} {{ $ribbonLine2 }}</div>

            <div class="recipient">{{ $applicantName }}</div>
            {{-- <div class="sub-recipient">{{ $associationLine }}</div> --}}

            <div class="panel">
                <table class="panel-table">
                    <tr><td class="label">Application reference</td><td class="value">{{ $applicationReference }}</td></tr>
                    <tr><td class="label">Association</td><td class="value">{{ $associationLine }}</td></tr>
                    <tr><td class="label">Applicant type</td><td class="value">{{ $applicantTypeLabel }}</td></tr>
                    <tr><td class="label">Member / author type</td><td class="value">{{ $authorTypeLabel }}</td></tr>
                    <tr><td class="label">Author category</td><td class="value">{{ $authorCategoryLabel }}</td></tr>
                    <tr><td class="label">Nationality</td><td class="value">{{ $nationality }}</td></tr>  
                    <tr><td class="label">Application status</td><td class="value">{{ $applicationStatusLabel }}</td></tr> 
                    <tr><td class="label">Consent recorded</td><td class="value">{{ $consentRecordedLine }}</td></tr>
                    <tr><td class="label">Submitted on</td><td class="value">{{ $submittedOn }}</td></tr>
                    <tr><td class="label">Admin reviewed on</td><td class="value">{{ $reviewedOn }}</td></tr>
                </table>
            </div>

            <div class="authorisation-block">
                <div class="auth-signature-only">{!! $executiveDirectorSignatureHtml !!}</div>
                <div class="auth-rule" role="presentation"></div>
                <div class="auth-authorised">Authorised.</div>
                <div class="auth-name">Oluwatosin Akeredolu</div>
                <div class="auth-role">Executive Director</div>
                <div class="auth-org">REPRONIG</div>
                <div class="auth-seal-wrap">{!! $certificateSealHtml !!}</div>
            </div>

            <div class="footer">
                This mandate is issued electronically and summarises the consent and membership data held on the REPRONIG platform.
                <div class="ref">Verification ref: {{ $referenceCode }}</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
