@component('emails.layouts.app')
@slot('subject') Verify Your Email Address @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Hello {{ $user->first_name ?? 'there' }},
</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Thank you for creating a Repronig account. Please verify your email address to complete your registration and continue using the platform securely.
</p>
<p style="margin:28px 0 30px;">
    <a href="{{ $verificationUrl }}" style="display:inline-block; background:#b01217; color:#ffffff; text-decoration:none; padding:15px 28px; border-radius:12px; font-size:16px; font-weight:700;">
        Verify Email Address
    </a>
</p>
<p style="margin:0 0 10px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    If the button does not work, copy and paste this link into your browser:
</p>
<p style="margin:0 0 24px; font-size:15px; line-height:1.7; word-break:break-word; color:#b01217;">
    {{ $verificationUrl }}
</p>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    If you did not create this account, no further action is required.
</p>
@endcomponent
