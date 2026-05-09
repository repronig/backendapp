@component('emails.layouts.app')
@slot('subject') Member Affiliation {{ ($decision ?? 'validated') === 'rejected' ? 'Rejected' : 'Validated' }} by Association @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello Admin,</p>
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    A member affiliation has been {{ ($decision ?? 'validated') === 'rejected' ? 'rejected' : 'validated' }} by an association officer and is ready for admin decision.
</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Applicant:</strong> {{ $application->user?->name ?? $application->user?->email }}<br>
    <strong>Association:</strong> {{ $application->association?->name }}<br>
    <strong>Decision:</strong> {{ ($decision ?? 'validated') === 'rejected' ? 'Affiliation Rejected' : 'Affiliation Validated' }}<br>
    <strong>Reviewed by:</strong> {{ $reviewer->name }}
</div>
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Please review the member application in the admin portal and take a final decision.
</p>
@endcomponent
