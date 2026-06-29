<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 14mm 12mm; }

        html, body {
            height: auto;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #1a1a1a;
            background: #ffffff;
        }

        .sheet {
            position: relative;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid rgba(106, 16, 37, 0.18);
            min-height: 190mm;
        }

        .watermark {
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            min-height: 190mm;
            z-index: 0;
            opacity: 0.035;
            background-repeat: repeat;
            background-size: 68px auto;
            background-position: 0 0;
            pointer-events: none;
        }

        .foreground {
            position: relative;
            z-index: 1;
        }

        .masthead {
            padding: 22px 24px 14px;
            border-bottom: 3px double #6a1025;
            text-align: center;
            background: linear-gradient(180deg, rgba(248, 242, 232, 0.55) 0%, rgba(255, 255, 255, 0) 100%);
        }

        .platform {
            font-size: 10pt;
            font-weight: 700;
            color: #2b0a12;
            margin: 4px 0 0;
            letter-spacing: 0.02em;
        }

        .generated {
            font-size: 8pt;
            color: #667085;
            margin: 6px 0 0;
        }

        .body {
            padding: 18px 24px 24px;
        }

        .title {
            font-size: 15pt;
            font-weight: 700;
            margin: 0 0 4px;
            color: #6a1025;
            text-align: center;
            letter-spacing: 0.04em;
        }

        .title-sub {
            text-align: center;
            font-size: 8.5pt;
            color: #667085;
            margin: 0 0 18px;
            line-height: 1.45;
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

        .section-intro {
            font-size: 8.5pt;
            color: #475467;
            margin: 0 0 10px;
            line-height: 1.45;
        }

        .section {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        th, td {
            border: 1px solid rgba(106, 16, 37, 0.14);
            padding: 7px 8px;
            vertical-align: top;
            text-align: left;
        }

        thead th {
            background: #FCFCF7;
            color: #5a3d45;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        tbody tr:nth-child(even) td {
            background: rgba(248, 242, 232, 0.35);
        }

        .col-name {
            width: 18%;
            white-space: nowrap;
        }

        .col-required {
            width: 10%;
            white-space: nowrap;
            text-align: center;
        }

        .code {
            font-family: DejaVu Sans Mono, Courier, monospace;
            font-size: 8pt;
            color: #7A1F1A;
            background: #F8F2E8;
            padding: 2px 5px;
            border-radius: 3px;
        }

        .note {
            font-size: 8.5pt;
            line-height: 1.4;
            color: #344054;
        }

        .required-yes {
            color: #027A48;
            font-weight: 700;
            font-size: 8.5pt;
        }

        .required-no {
            color: #667085;
            font-size: 8.5pt;
        }

        .zip-pattern {
            width: 38%;
        }

        .footer {
            margin-top: 8px;
            padding-top: 10px;
            border-top: 1px solid rgba(106, 16, 37, 0.16);
            font-size: 7.5pt;
            color: #667085;
            text-align: center;
            line-height: 1.5;
        }

        .footer strong {
            color: #2b0a12;
        }
    </style>
</head>
<body>
<div class="sheet">
    @if(!empty($watermarkDataUri))
        <div class="watermark" style="background-image:url('{{ $watermarkDataUri }}');"></div>
    @endif
    <div class="foreground">
        <header class="masthead">
            {!! $brandLogoHtml !!}
            <div class="platform">{{ $platformName }}</div>
            <div class="generated">Generated {{ $generatedAt }}</div>
        </header>

        <main class="body">
            <h1 class="title">CSV column reference</h1>
            <p class="title-sub">
                Bulk works import template — column names, allowed values, and ZIP file naming rules.
            </p>

            <section class="section">
                <div class="section-label">CSV columns</div>
                <p class="section-intro">
                    Header row names must match exactly. Required fields must be filled on every row.
                </p>
                <table>
                    <thead>
                    <tr>
                        <th class="col-name">Column</th>
                        <th>Description</th>
                        <th class="col-required">Required</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($columns as $column)
                        <tr>
                            <td class="col-name"><span class="code">{{ $column['column'] }}</span></td>
                            <td><span class="note">{{ $column['note'] }}</span></td>
                            <td class="col-required">
                                <span class="{{ $column['required'] ? 'required-yes' : 'required-no' }}">
                                    {{ $column['required'] ? 'Yes' : 'No' }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>

            <section class="section">
                <div class="section-label">ZIP file naming (Step 4)</div>
                <p class="section-intro">
                    After draft works are created, name cover and supporting files using each row&apos;s
                    <span class="code">identifier_value</span>.
                </p>
                <table>
                    <thead>
                    <tr>
                        <th class="zip-pattern">File pattern</th>
                        <th>Description</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($zipFiles as $zipFile)
                        <tr>
                            <td class="zip-pattern"><span class="code">{{ $zipFile['pattern'] }}</span></td>
                            <td><span class="note">{{ $zipFile['note'] }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>

            <footer class="footer">
                Use this reference when completing the bulk works import CSV template and preparing your ZIP archive.
                <br><strong>{{ $platformName }}</strong>
            </footer>
        </main>
    </div>
</div>
</body>
</html>
