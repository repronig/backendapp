<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>REPRONIG — Member mandate</title>
    <style>
        @page { margin: 18mm 16mm; }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            color: #1e2024;
            margin: 0;
        }
        h1 {
            color: #6a1025;
            font-size: 16pt;
            margin: 0 0 6pt;
            font-weight: 700;
        }
        .lede {
            color: #475467;
            margin: 0 0 14pt;
            line-height: 1.45;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4pt;
        }
        td {
            padding: 8pt 6pt;
            border-bottom: 1pt solid #eaecf0;
            vertical-align: top;
        }
        td.label {
            font-weight: 700;
            width: 36%;
            color: #344054;
        }
        .footer {
            margin-top: 18pt;
            font-size: 8.5pt;
            color: #667085;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <h1>REPRONIG — Member mandate record</h1>
    <p class="lede">
        This PDF summarises your approved membership application and the consent / mandate details held by
        Reproduction Rights Organisation of Nigeria (REPRONIG). Retain a copy for your records.
    </p>
    <table>
        <tr>
            <td class="label">Application reference</td>
            <td>{{ $applicationReference }}</td>
        </tr>
        <tr>
            <td class="label">Applicant</td>
            <td>{{ $applicantName }}</td>
        </tr>
        <tr>
            <td class="label">Association</td>
            <td>{{ $associationName }}</td>
        </tr>
        <tr>
            <td class="label">Application status</td>
            <td>{{ $applicationStatus }}</td>
        </tr>
        <tr>
            <td class="label">Affiliation status</td>
            <td>{{ $affiliationStatus }}</td>
        </tr>
        <tr>
            <td class="label">Consent accepted</td>
            <td>{{ $consentAccepted }}</td>
        </tr>
        <tr>
            <td class="label">Consent date</td>
            <td>{{ $consentDate }}</td>
        </tr>
        <tr>
            <td class="label">Submitted at</td>
            <td>{{ $submittedAt }}</td>
        </tr>
        <tr>
            <td class="label">Admin reviewed at</td>
            <td>{{ $reviewedAt }}</td>
        </tr>
    </table>
    <p class="footer">
        Generated electronically by the REPRONIG member platform. For questions, contact REPRONIG support.
    </p>
</body>
</html>
