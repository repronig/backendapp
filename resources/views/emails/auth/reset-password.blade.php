@component('emails.layouts.app')
@slot('subject') Reset Your REPRONIG Password @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Hello {{ $user->first_name ?? 'there' }},
</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    We received a request to reset the password for your REPRONIG account.
</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Click the button below to choose a new password. This link will expire in {{ $expiryMinutes }} minutes.
</p>
<p style="margin:28px 0 30px;">
    <a href="{{ $resetUrl }}" style="display:inline-block; background:#b01217; color:#ffffff; text-decoration:none; padding:15px 28px; border-radius:12px; font-size:16px; font-weight:700;">
        Reset Password
    </a>
</p>
<p style="margin:0 0 10px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    If the button does not work, copy and paste this link into your browser:
</p>
<p style="margin:0 0 24px; font-size:15px; line-height:1.7; word-break:break-word; color:#b01217;">
    {{ $resetUrl }}
</p>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    If you did not request a password reset, you can safely ignore this email.
</p>
@endcomponent
