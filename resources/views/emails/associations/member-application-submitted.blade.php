@component('emails.layouts.app')
@php
    $applicant = $memberApplication->user;
    $applicantName = $applicant?->name ?? $applicant?->email ?? 'Applicant';
    $association = $memberApplication->association;
    $reference = $memberApplication->application_reference ?: $memberApplication->external_id ?: 'Pending reference';
@endphp
@slot('subject') Member Affiliation Request for {{ $applicantName }} @endslot
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">Hello,</p>
<p style="margin:0 0 18px; font-size:18px; line-height:1.7; color:#2b2b2b;">
    A new member application has been submitted for {{ $association?->name ?? 'your association' }}.
</p>
<div style="margin:0 0 22px; background:#faf7f2; border-radius:14px; padding:18px 20px; font-size:16px; line-height:1.8; color:#3d3d3d;">
    <strong>Applicant:</strong> {{ $applicant?->name ?? $applicant?->email ?? 'Unknown applicant' }}<br>
    <strong>Reference:</strong> {{ $reference }}<br>
    <strong>Stage:</strong> {{ $memberApplication->submission_stage ?? 'under_association_review' }}
</div>
<p style="margin:0 0 18px; text-align:center;">
    <a href="{{ $verifyAffiliationUrl }}" style="display:inline-block; background:#b01217; color:#ffffff; text-decoration:none; font-weight:bold; border-radius:12px; padding:14px 24px;">
        Verify Affiliation
    </a>
</p>
@endcomponent
