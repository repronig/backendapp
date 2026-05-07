@component('emails.layouts.app')
@slot('subject') Annual Declaration Rejected @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $declaration->institution->contact_person_name ?: $declaration->institution->name }},</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    Your annual declaration for {{ $declaration->licensing_year }} has been rejected.
</p>
<p style="margin:0 0 20px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Please review the rejection details and update your submission where needed before submitting again.
</p>
<div style="margin:0 0 24px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Declaration Year:</strong> {{ $declaration->licensing_year }}<br>
    <strong>Status:</strong> Rejected<br>
    @if($reason)
        <strong>Reason:</strong> {{ $reason }}
    @else
        <strong>Reason:</strong> See declaration details in your institution portal.
    @endif
</div>
<p style="margin:0 0 18px; text-align:center;">
    <a href="{{ $declarationsUrl }}" style="display:inline-block; background:#f4c430; color:#7a1018; text-decoration:none; font-weight:bold; border-radius:12px; padding:14px 24px;">
        Review declaration
    </a>
</p>
<p style="margin:0; font-size:14px; line-height:1.6; color:#6b6b6b;">
    If you are not already signed in, the platform will ask you to log in before showing your declaration records.
</p>
@endcomponent
