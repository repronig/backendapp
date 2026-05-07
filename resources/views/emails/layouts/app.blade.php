<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>{{ $subject ?? config('app.name', 'REPRONIG') }}</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-rspace: 0pt; mso-table-lspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; display: block; }
        body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; background-color: #efefef; font-family: Arial, Helvetica, sans-serif; color: #222222; }
        a { color: #b01217; }
        .mobile-shell { width: 100%; max-width: 640px; }
        @media only screen and (max-width: 640px) {
            .mobile-shell { width: 100% !important; }
            .card-padding { padding: 32px 24px !important; }
            .logo-wrap { padding: 34px 20px 18px !important; }
            .footer-wrap { padding: 14px 20px 28px !important; }
            .heading { font-size: 28px !important; line-height: 36px !important; }
        }
    </style>
</head>
<body>
    @php
        $logoUrl = \App\Support\Mail\EmailBrandAssets::logoImgSrc();
        $supportEmail = config('mail.support_email', 'support@repronig.org');
        $resolvedSubject = $subject ?? config('app.name', 'REPRONIG');
    @endphp

    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        {{ $resolvedSubject }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#efefef;">
        <tr>
            <td align="center" class="logo-wrap" style="padding:42px 24px 20px;">
                <img src="{{ $logoUrl }}" alt="REPRONIG" width="250" style="width:250px; max-width:80%; height:auto; margin:0 auto;">
            </td>
        </tr>
        <tr>
            <td align="center" style="padding:0 16px;">
                <table role="presentation" class="mobile-shell" width="640" cellpadding="0" cellspacing="0" border="0" style="width:100%; max-width:640px; background:#ffffff; border-radius:22px; overflow:hidden;">
                    <tr>
                        <td class="card-padding" style="padding:44px 46px 46px; font-size:18px; line-height:1.7; color:#2b2b2b;">
                            {{ $slot }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td align="center" class="footer-wrap" style="padding:16px 24px 34px; font-size:12px; line-height:1.6; color:#8b8b8b;">
                © {{ now()->year }} REPRONIG. All rights reserved.
            </td>
        </tr>
    </table>
</body>
</html>
