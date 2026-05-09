@component('emails.layouts.app')
@slot('subject') {{ $subject }} @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello {{ $application->user?->name ?? 'there' }},</p>
@if(($decision ?? 'validated') === 'rejected')
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    <strong>{{ $application->association?->name ?? 'Your association' }}</strong> has declined your membership affiliation.
</p>
@if(filled($application->affiliation_review_note))
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Note from the association:</strong><br>{{ $application->affiliation_review_note }}
</div>
@endif
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    You can review your application in the REPRONIG member portal. If you have questions, contact your association or REPRONIG support.
</p>
@else
<p style="margin:0 0 20px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    <strong>{{ $application->association?->name ?? 'Your association' }}</strong> has validated your membership affiliation.
</p>
<p style="margin:0 0 22px; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Your application is now with REPRONIG for an admin review. We will notify you when a final decision is made.
</p>
@endif
@if(!empty($platformUrl))
<p style="margin:0 0 24px; font-size:16px; line-height:1.7;">
    <a href="{{ rtrim($platformUrl, '/') }}/member/onboarding" style="display:inline-block; padding:12px 18px; background:#b01217; color:#ffffff; text-decoration:none; border-radius:8px; font-weight:700;">Open My Mandate</a>
</p>
@endif
<p style="margin:0; font-size:16px; line-height:1.7; color:#4c4c4c;">
    Thank you for using REPRONIG.
</p>
@endcomponent
