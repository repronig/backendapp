@component('emails.layouts.app')
@slot('subject') Your REPRONIG OTP Code @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Hello {{ $user->first_name ?? 'there' }},
</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Use the security code below to continue your {{ $purposeLabel ?? 'verification' }}.
</p>
<p style="margin:28px 0 28px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    <span style="display:inline-block; background:#fff4f4; color:#b01217; border-radius:14px; padding:14px 22px; font-size:24px; font-weight:700; letter-spacing:4px;">
        {{ $code }}
    </span>
</p>
<p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    This code expires in {{ $expiryMinutes }} minutes.
</p>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    If you did not request this code, please secure your account immediately or contact support@repronig.org.
</p>
@endcomponent
