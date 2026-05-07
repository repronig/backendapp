@component('emails.layouts.app')
@slot('subject') Welcome to REPRONIG @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $user->first_name ?? 'there' }},</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Welcome to REPRONIG. Your membership application has been approved by your association.
</p>
@if($memberCode)
    <p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
        Your member code is:
    </p>
    <p style="margin:0 0 24px; font-size:30px; line-height:1.2; color:#b01217; font-weight:700; letter-spacing:1px;">
        {{ $memberCode }}
    </p>
@endif
<p style="margin:0 0 18px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    You can now continue on the platform, complete your profile where necessary, and register your works.
</p>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Thank you for joining REPRONIG.
</p>
@endcomponent
