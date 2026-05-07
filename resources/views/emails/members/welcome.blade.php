@component('emails.layouts.app')
@slot('subject') Welcome to REPRONIG @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $memberUser?->name ?? 'there' }},</p>
<p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Welcome to REPRONIG. Your member account is now verified and ready to use.
</p>
<p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    You can complete your profile, register your works, track review updates, and manage your rights-related activities in one place.
</p>
<p style="margin:0 0 24px; font-size:16px; line-height:1.7;">
    <a href="{{ $platformUrl }}" style="display:inline-block; padding:12px 18px; background:#b01217; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:700;">Go to my dashboard</a>
</p>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Thanks for joining REPRONIG.
</p>
@endcomponent
