@component('emails.layouts.app')
@slot('subject') New Member Approved by Association @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello Admin,</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    A member application has been approved by an association officer.
</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Applicant:</strong> {{ $application->user?->name ?? $application->user?->email }}<br>
    <strong>Association:</strong> {{ $application->association?->name }}<br>
    <strong>Approved by:</strong> {{ $reviewer->name }}
</div>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Please review the updated member record in the admin portal.
</p>
@endcomponent
