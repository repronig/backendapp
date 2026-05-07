@component('emails.layouts.app')
@slot('subject') Institution Registration Decision @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $institution->contact_person_name ?: $institution->name }},</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Your institution registration has been rejected on REPRONIG.
</p>
@if($reason)
<div style="margin:0 0 22px; background:#fff4f4; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Reason:</strong><br>{{ $reason }}
</div>
@endif
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Institution:</strong> {{ $institution->name }}
</div>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    If you believe this decision was made in error, please contact REPRONIG support using the email shown in your invitation or profile.
</p>
@endcomponent
