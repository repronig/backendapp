<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20px 26px; }

        html, body {
            height: auto;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            color: #1a1a1a;
            background: #ffffff;
        }

        .sheet {
            position: relative;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid rgba(106, 16, 37, 0.22);
            box-shadow: 0 0 0 5px rgba(250, 246, 240, 0.9);
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
            opacity: 0.038;
            background-repeat: repeat;
            background-size: 72px auto;
            background-position: 0 0;
            pointer-events: none;
        }

        .receipt-foreground {
            position: relative;
            z-index: 1;
            background: transparent;
        }

        .masthead {
            padding: 40px 32px 18px;
            border-bottom: 3px double #6a1025;
            background: transparent;
            text-align: center;
        }

        .platform {
            font-size: 11pt;
            font-weight: 700;
            color: #2b0a12;
            margin: 2px 0 0;
            letter-spacing: 0.02em;
        }

        .generated {
            font-size: 8.5pt;
            color: #667085;
            margin: 8px 0 0;
        }

        .body {
            padding: 22px 32px 32px;
            background: transparent;
        }

        .title {
            font-size: 17pt;
            font-weight: 700;
            margin: 0 0 4px;
            color: #6a1025;
            text-align: center;
            letter-spacing: 0.04em;
        }

        .title-sub {
            text-align: center;
            font-size: 9pt;
            color: #667085;
            margin: 0 0 22px;
        }

        .amount-hero {
            margin: 0 auto 26px;
            max-width: 100%;
            padding: 20px 24px 22px;
            text-align: center;
            border: 2px double rgba(106, 16, 37, 0.55);
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.72);
            box-shadow: 0 1px 0 rgba(255, 255, 255, 0.9) inset;
        }

        .amount-hero-label {
            font-size: 8pt;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: #6a1025;
            font-weight: 700;
            margin: 0 0 8px;
        }

        .amount-hero-value {
            font-size: 22pt;
            font-weight: 700;
            color: #129242;
            letter-spacing: 0.02em;
            line-height: 1.15;
        }

        .section-label {
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #6a1025;
            font-weight: 700;
            margin: 0 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid rgba(106, 16, 37, 0.2);
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 20px;
        }

        .meta td {
            padding: 10px 8px 10px 0;
            vertical-align: top;
            border-bottom: 1px solid rgba(106, 16, 37, 0.1);
        }

        .meta tr:nth-child(even) td {
            background: rgba(106, 16, 37, 0.03);
        }

        .meta tr:last-child td {
            border-bottom: 0;
        }

        .k {
            width: 36%;
            font-size: 8.25pt;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #5a3d45;
            font-weight: 700;
        }

        .v {
            font-weight: 600;
            color: #1a1a1a;
            font-size: 10.25pt;
        }

        .paid-at-time {
            font-weight: 600;
            color: #1a1a1a;
        }

        .footer {
            margin-top: 26px;
            padding-top: 14px;
            border-top: 1px solid rgba(106, 16, 37, 0.18);
            font-size: 7.75pt;
            color: #667085;
            text-align: center;
            line-height: 1.55;
        }

        .footer strong {
            color: #2b0a12;
            font-weight: 700;
        }
    </style>
</head>
<body>
<div class="sheet">
    @if(!empty($watermarkDataUri))
        <div class="watermark" style="background-image:url('{!! $watermarkDataUri !!}');"></div>
    @endif
    <div class="receipt-foreground">
        <div class="masthead">
            {!! $brandLogoHtml !!}
            <div class="platform">{{ $platformName }}</div>
            <div class="generated">Generated {{ $generatedAt }}</div>
        </div>

        <div class="body">
            <div class="title">{{ $documentTitle }}</div>
            <div class="title-sub">Confirmation of a successful licence payment</div>

            <div class="amount-hero">
                <div class="amount-hero-label">Amount received</div>
                <div class="amount-hero-value">{{ $currencySymbol }} {{ $amount }}</div>
            </div>

            <div class="section-label">Payment details</div>
            <table class="meta" cellspacing="0">
                <tr>
                    <td class="k">Receipt number</td>
                    <td class="v">{{ $receiptNo }}</td>
                </tr>
                <tr>
                    <td class="k">Date paid</td>
                    <td class="v">{{ $paidOn }}@if($paidAtTime) <span class="paid-at-time">{{ $paidAtTime }}</span>@endif</td>
                </tr>
                <tr>
                    <td class="k">Payer</td>
                    <td class="v">{{ $institutionName }}</td>
                </tr>
                <tr>
                    <td class="k">Status</td>
                    <td class="v">{{ $paymentStatusLabel }}</td>
                </tr>
                <tr>
                    <td class="k">Invoice</td>
                    <td class="v">{{ $invoiceNumber }}</td>
                </tr>
                <tr>
                    <td class="k">Licence</td>
                    <td class="v">{{ $licenceNumberLine }}</td>
                </tr>
                <tr>
                    <td class="k">Gateway</td>
                    <td class="v">{{ $gatewayLabel }}</td>
                </tr>
                <tr>
                    <td class="k">Gateway reference</td>
                    <td class="v">{{ $gatewayReference }}</td>
                </tr>
            </table>

            <div class="footer">
                Thank you for your payment. Please retain this receipt for your records.<br>
                <strong>{{ $platformName }}</strong>
            </div>
        </div>
    </div>
</div>
</body>
</html>
